
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
- **Ansible privilege escalation: use `become: true`.** Minis have
  passwordless sudo for the SSH user (`ALL=(root) NOPASSWD: ALL`). Use
  normal module forms (`template`, `copy`, `file`, `command`) with
  `become: true` for anything needing root, including writing directly to
  root-owned paths like `/Library/LaunchDaemons`. Don't render to `/tmp`
  and `sudo cp`, and don't put `sudo` in command strings.
- **Security boundary is SSH access, not sudoers.** The Maestro SSH key is
  effectively root on every Mini; key/access hardening belongs there.
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

The `common` and `mining` roles have been verified manually against real
hardware. Verification against real Minis is done by Joe, never by an agent.

- Never connect to real fleet hosts (ssh, scp, ansible, ansible-playbook)
  unless explicitly asked in the current task.
- Tests must never spawn real processes. Fake them with `Process::fake()`.
- Test hostnames use the `.invalid` TLD (e.g. `mini01.invalid`), never
  real fleet names and never `.test`.

## Actions

- `app/Actions/Ansible/BuildServerVars.php` — one server's Ansible host vars
  as an array: connection vars (`ansible_host`/`ansible_user`/`ansible_port`),
  `server_config` values, and `app_roles` (`always_apply` roles first, then
  assigned roles, each tier sorted by name).
- `app/Actions/Ansible/BuildInventory.php` — full inventory array for a given
  Collection of servers (caller decides which; no status filtering inside).
  Hosts defined once under `all.hosts` via `BuildServerVars`; one child group
  per assigned role with that role's `role_config` as group vars;
  `always_apply` roles' `role_config` goes in `all.vars`. Only groups that
  have hosts are emitted.

## Commands

- `ansible:inventory {servers?* : Server names (default: all)}` - Output the Ansible inventory for the given servers, or all servers
- `ansible:vars {server} {--output=}` - Output the merged Ansible variables for a server
- `role:config {role} {key} {value}` - Set a configuration value for a role
- `role:config-delete {role} {key}` - Delete a configuration value for a role
- `role:config-list {role}` - List all configuration values for a role
- `role:list` - List discovered Ansible roles and their registration status
- `role:register {role} {label} {--always-apply}` - Register a discovered role with a label and optional `always_apply` flag
- `server:add {name} {hostname} {ssh-user} {--ssh-port=22}` - Register or update a server (upsert by name)
- `server:config {server} {key} {value}` - Set a configuration value for a server
- `server:config-delete {server} {key}` - Delete a configuration value for a server
- `server:config-list {server}` - List all configuration values for a server
- `server:list` - List all registered servers
- `server:role-add {server} {roles*}` - Add one or more roles to a server
- `server:role-remove {server} {roles*}` - Remove one or more roles from a server
- `ansible:inventory {servers?*}` — print the inventory JSON for the named
  servers, or all.
- `ansible:run {playbook} {--server=*} {--all} {--check} {--diff}` — run a
  playbook through `AnsibleRunner`, streaming output live. Requires exactly one
  of `--server` / `--all` (never the whole fleet by default). Prints an outcome
  summary and exits with Ansible's exit code.

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

## Not yet built

- `EditServer` Livewire component
- Add Server modal (two-step: instructions, then `ServerForm`)
- MFA on this app (planned tie-in with a separate MFA learning project once
  Fortify's TOTP support is wired up)
