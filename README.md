# MiniFleet Maestro

A Laravel control plane for provisioning and orchestrating a small fleet of Mac Minis over SSH with Ansible.

> **Status:** early development. Core pieces work end to end against real hardware, but the web UI and much of the provisioning surface are still being built. Expect breaking changes.

## What it is

MiniFleet Maestro manages a fleet of headless Mac Minis that run mixed workloads — things like local LLM inference nodes and other long-running compute services. It replaces a manual, per-machine setup process with repeatable, idempotent provisioning.

The project has two layers:

- **Ansible layer** — agentless roles and playbooks that do the actual work on each Mini over SSH. No agent or daemon is installed on managed machines.
- **Laravel control plane** — the application that owns fleet inventory, per-server and per-role configuration, and secrets, and that drives Ansible runs from either the command line or (eventually) the browser.

## Goals

- **Single source of truth for the fleet.** Servers, their assigned roles, and their configuration live in the application's database rather than in hand-maintained inventory files.
- **Nothing sensitive in the repository.** No inventory files, hostnames, or secrets are committed in any form — plaintext or encrypted. Ansible inventory is generated per run from the database and discarded afterward; secrets are encrypted at rest in the database.
- **Repeatable, idempotent provisioning.** Bringing a new Mini online, or re-applying configuration to an existing one, should be a single run that is safe to repeat.
- **Fail fast on what can't be automated.** Prerequisites that require hands-on interaction with a Mini are verified, and a run stops with clear remediation instructions rather than attempting an unattended fix that can't complete.
- **Role-based composition.** Each Mini gets a common baseline plus whichever workload roles are assigned to it, so one fleet can serve several purposes.
- **CLI first, UI second.** Real work lives in UI-agnostic action and service classes, exposed first through Artisan commands; the web interface is a thin layer over the same code.
- **Visibility.** Longer term: run playbooks as queued jobs with live output in the browser, and surface fleet health and configuration drift.

## Requirements

### Control node

- PHP and Composer (versions per `composer.json`)
- Node.js and npm (for frontend assets)
- Ansible (`ansible-playbook` available on the path, or configured via environment)
- SSH key-based access to each managed Mini

### Managed Macs

Each Mini needs a short one-time manual bootstrap before Maestro can manage it:

- macOS with an initial local admin account created for SSH/Ansible use
- A stable, DNS-resolvable hostname
- **Remote Login** enabled
- **Xcode Command Line Tools** installed (provides the Python interpreter Ansible needs)
- **FileVault disabled** (required for unattended recovery after a power loss)
- Passwordless `sudo` for the SSH user

Maestro verifies these prerequisites at the start of a run and reports what's missing.

## Stack

Laravel · Livewire · Flux · Laravel Fortify · Ansible

## License

TBD
