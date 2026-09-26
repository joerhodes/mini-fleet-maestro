
# MiniFleet Maestro — Laravel Control Plane

Laravel app that's the control plane for MiniFleet Maestro, an Ansible/SSH-based
provisioning tool for a fleet of Mac Minis. Portfolio piece + real infrastructure.

## Stack

- Laravel, Livewire 4, Flux 2
- Fortify (headless) for auth 
- SQLite for local dev (Herd)
- Ansible (agentless, over SSH) drives the actual Mac Mini fleet

## Conventions

- **Blade + controller by default.** Reach for Livewire only when something
  needs to update in place without a page reload (status badges after an async
  check, streamed output). Not every form needs to be a Livewire component.
- **Form objects are shared** between create/edit Livewire components
  (e.g. `ServerForm` used by both the Add Server modal and `EditServer`).
  Validation rules live once, on the Form object.
- **camelCase for PHP properties**, including on Form objects — snake_case is
  reserved for actual DB columns and array keys passed to Eloquent. Map
  explicitly (`'ssh_user' => $this->sshUser`) rather than naming properties
  after columns for convenience.
- **Role identity = the literal Ansible role folder name**
  (`resources/ansible/roles/<name>`), never a DB-generated ID. `roles.role`,
  `server_role.role`, and `role_config.role` are all this same string.
  `roles` is a real gate, not decoration — a role isn't assignable until it
  has a row there (enforced via FK from `server_role`/`role_config`).
- **Never commit secrets, in any form** — not plaintext, not
  `ansible-vault`-encrypted. Rejected `ansible-vault` for per-Mini sudo
  passwords and for wallet/pool config: too much ongoing friction (password
  rotation, new-Mini onboarding) and real exposure risk for a public
  portfolio repo. Secrets live in the database instead, via Laravel's
  `encrypted` Eloquent cast:
  - `server_config` — per-server secret data (e.g. `rig_id`)
  - `role_config` — shared secret/config per role (e.g. `wallet_id`, `pool_url`)
- **Ansible inventory is never a committed file.** A static inventory leaks
  fleet size, hostnames, or DNS names even when "sensitive" fields are
  gitignored. `AnsibleRunner` generates a temp inventory per run, sourced from the
  `servers` table, written (0600) under `storage/app/private/ansible/` and
  deleted in a `finally` block. Never log or echo its contents.
- **Ansible privilege escalation: explicit `sudo` in the command string,
  not `become: true`.** `become` wraps commands in a shell
  (`sudo ... /bin/sh -c '...'`), which doesn't match narrowly-scoped sudoers
  entries — sudo ends up authorizing `/bin/sh`, not the actual binary. Use
  `ansible.builtin.command: "sudo <binary> ..."` instead, so sudo sees the
  real command and narrow sudoers rules work as written.
- **Sudoers entries must use the exact binary path** — verify with `which`,
  don't assume `/usr/bin/x` vs `/bin/x`. Got bitten by this once (`cp` is
  `/bin/cp` on macOS, not `/usr/bin/cp`).
- **Ansible task files are split by concern**, not one long `main.yml`.
  `roles/<role>/tasks/main.yml` is a thin index using `include_tasks`;
  each concern gets its own file (e.g. `xcode-clt.yml`, `filevault.yml`).
- Roles that apply to every server regardless of assignment (currently just
  `common`) are marked via `roles.always_apply`, not a config array.
  `AnsibleRoleRegistry::assignable()` excludes them from what a user can pick.

## Current schema (servers domain)

- `servers` — core identity/connection: `name`, `hostname`, `ssh_user`,
  `ssh_port`, `status`, `last_checked_at`, `last_check_output`, `notes`
- `server_config` — per-server secret data, key/value, encrypted
- `roles` — registered, assignable Ansible roles: `role` (PK, matches folder
  name), `label`, `always_apply`
- `server_role` — which roles a server has (pivot, composite PK)
- `role_config` — shared secret/config per role, key/value, encrypted

`Server::status` is a backed PHP enum (`App\Enums\ServerStatus`):
`pending`, `testing`, `connected`, `failed`. Editing a server's connection
fields (`hostname`, `ssh_user`, `ssh_port`) resets status to `pending` and
clears `last_checked_at`/`last_check_output` — a `connected` badge next to
just-changed details is misleading.

## Ansible layer

```
resources/ansible/
  ansible.cfg          # roles_path = ./roles
  playbooks/
    bootstrap.yml       # roles: "{{ app_roles }}" (planned; currently hardcoded to `common`)
  roles/
    common/
      tasks/
        main.yml         # index — include_tasks per concern
        xcode-clt.yml     # verify-only guard, fails with instructions if missing
        filevault.yml     # verify-only guard, fails with instructions if enabled
        power-management.yml  # pmset settings + LaunchDaemon deploy (idempotent)
      handlers/
        main.yml
      templates/
        pmset-enforcer.plist.j2
```

Config: `config/ansible.php` — `paths.root` / `paths.roles` / `paths.playbooks` /
`paths.inventory` (nested under `paths`), plus `binary` (`ANSIBLE_PLAYBOOK_BIN`)
and `timeout` (`ANSIBLE_TIMEOUT`, default 1800s).

`common` role verified working end-to-end against a real Mini (`msv05`) as of
this writing: Xcode CLT check, FileVault check, pmset settings + enforcer
LaunchDaemon all passing.

## Services

- `app/Services/AnsibleRoleRegistry.php` — role discovery/registration:
  - `discovered()` — filesystem scan of `paths.roles`, folders with a valid
    `tasks/main.yml`
  - `registered()` — `Role::pluck('role')`
  - `unregistered()` — `discovered()` diff `registered()`
  - `assignable()` — registered roles where `always_apply = false`
- `app/Services/AnsibleRunner.php` — `run($playbook, $servers, $check, $diff, $onOutput)`:
  builds the inventory via `BuildInventory`, writes it to a temp file, runs
  `ansible-playbook` (Laravel `Process`, array-form command, `--limit` the
  server names) from `paths.root`, deletes the file, and returns an
  `App\Support\Ansible\AnsibleRunResult` (`exitCode`, `output`, `errorOutput`,
  `outcome()`). Playbook names must match `[A-Za-z0-9_-]+` and exist under
  `paths.playbooks`; otherwise `InvalidArgumentException`. Synchronous only.
- `App\Enums\AnsibleRunOutcome::fromExitCode()` is the single exit-code mapping:
  0 Success, 2 HostFailed, 4 Unreachable (verified on Ansible core 2.21),
  anything else Error.

## Commands

- `ansible:inventory {servers?*}` — print the inventory JSON for the named
  servers, or all.
- `ansible:run {playbook} {--server=*} {--all} {--check} {--diff}` — run a
  playbook through `AnsibleRunner`, streaming output live. Requires exactly one
  of `--server` / `--all` (never the whole fleet by default). Prints an outcome
  summary and exits with Ansible's exit code.
- Both share server-name resolution via `App\Traits\ResolvesServers`.

## Not yet built

- Artisan commands for server registration + triggering Ansible runs
  (intent: same underlying Action classes usable from both CLI and UI —
  UI is deliberately being deferred while these are built)
- `EditServer` Livewire component
- Add Server modal (two-step: instructions, then `ServerForm`)
- xmrig role (bootstrap install + LaunchDaemon, replacing an old
  auto-login + Automator approach — abandoned in favor of a LaunchDaemon,
  same pattern as `power-management.yml`)
- MFA on this app (planned tie-in with a separate MFA learning project once
  Fortify's TOTP support is wired up)
