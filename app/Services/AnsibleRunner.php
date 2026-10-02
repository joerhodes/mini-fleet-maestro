<?php

namespace App\Services;

use App\Actions\Ansible\BuildInventory;
use App\Models\Server;
use App\Support\Ansible\AnsibleRunResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AnsibleRunner
{
    public function __construct(protected BuildInventory $buildInventory) {}

    /**
     * Run a playbook against the given servers using a temporary inventory file.
     *
     * The inventory contains decrypted secrets, so it is created with 0600 permissions,
     * never logged, and always deleted, even when the process throws (e.g. on timeout).
     *
     * @param  string  $playbook  Playbook name without extension, e.g. "bootstrap"
     * @param  Collection<int, Server>  $servers
     * @param  (callable(string, string): void)|null  $onOutput  Receives the stream type and each output chunk
     * @param  int|null  $connectionTimeout  When given, overrides Ansible's own connection timeout (via
     *                                       `ANSIBLE_TIMEOUT`) and caps the Laravel-side process timeout to match,
     *                                       instead of waiting up to the full `ansible.timeout` for a hung host.
     *
     * @throws InvalidArgumentException When the playbook name is invalid or no servers are given
     */
    public function run(
        string $playbook,
        Collection $servers,
        bool $check = false,
        bool $diff = false,
        ?callable $onOutput = null,
        ?int $connectionTimeout = null,
    ): AnsibleRunResult {
        $playbookPath = $this->playbookPath($playbook);

        if ($servers->isEmpty()) {
            throw new InvalidArgumentException('At least one server is required to run a playbook.');
        }

        $inventory = json_encode(
            ['all' => $this->buildInventory->handle($servers)],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        $inventoryPath = $this->newInventoryPath();

        $env = ['ANSIBLE_FORCE_COLOR' => '0', ...$this->sshEnvironment()];
        $timeout = config('maestro.ansible.timeout');

        if ($connectionTimeout !== null) {
            $env['ANSIBLE_TIMEOUT'] = (string) $connectionTimeout;
            $timeout = $connectionTimeout + 5;
        }

        try {
            $this->writeInventory($inventoryPath, $inventory);

            $result = Process::path(config('maestro.ansible.paths.root'))
                ->timeout($timeout)
                ->env($env)
                ->run(
                    $this->command($inventoryPath, $playbookPath, $servers, $check, $diff),
                    $onOutput,
                );

            return new AnsibleRunResult(
                exitCode: $result->exitCode() ?? 1,
                output: $result->output(),
                errorOutput: $result->errorOutput(),
            );
        } finally {
            File::delete($inventoryPath);
        }
    }

    /**
     * @param  Collection<int, Server>  $servers
     * @return array<int, string>
     */
    protected function command(
        string $inventoryPath,
        string $playbookPath,
        Collection $servers,
        bool $check,
        bool $diff,
    ): array {
        $command = [
            config('maestro.ansible.binary'),
            '-i', $inventoryPath,
            $playbookPath,
            '--limit', $servers->pluck('name')->implode(','),
        ];

        if ($check) {
            $command[] = '--check';
        }

        if ($diff) {
            $command[] = '--diff';
        }

        return $command;
    }

    /**
     * Point Ansible's SSH at the application's own key and known_hosts, rather than the invoking user's ssh config/agent.
     *
     * @return array<string, string>
     */
    protected function sshEnvironment(): array
    {
        $identityFile = config('maestro.connectivity.ssh.identity_file');
        $knownHosts = config('maestro.connectivity.ssh.known_hosts');

        $args = ['-o', 'IdentitiesOnly=yes', '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=accept-new'];

        if ($knownHosts) {
            $args = [...$args, '-o', 'UserKnownHostsFile='.$knownHosts];
        }

        $env = ['ANSIBLE_SSH_COMMON_ARGS' => implode(' ', array_map('escapeshellarg', $args))];

        if ($identityFile) {
            $env['ANSIBLE_PRIVATE_KEY_FILE'] = $identityFile;
        }

        return $env;
    }

    protected function playbookPath(string $playbook): string
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $playbook)) {
            throw new InvalidArgumentException("Invalid playbook name [{$playbook}].");
        }

        $path = config('maestro.ansible.paths.playbooks')."/{$playbook}.yml";

        if (! File::isFile($path)) {
            throw new InvalidArgumentException("The playbook [{$playbook}] was not found.");
        }

        return $path;
    }

    protected function newInventoryPath(): string
    {
        $directory = config('maestro.ansible.paths.inventory');

        File::ensureDirectoryExists($directory, 0700);

        return $directory.'/inventory-'.Str::uuid().'.json';
    }

    /**
     * Create the file with 0600 permissions before any secrets are written to it.
     */
    protected function writeInventory(string $path, string $contents): void
    {
        touch($path);
        chmod($path, 0600);

        File::put($path, $contents);
    }
}
