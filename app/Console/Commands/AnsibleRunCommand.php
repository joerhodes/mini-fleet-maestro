<?php

namespace App\Console\Commands;

use App\Enums\AnsibleRunOutcome;
use App\Services\AnsibleRunner;
use App\Traits\ResolvesServers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use InvalidArgumentException;
use RuntimeException;

#[Signature('ansible:run {playbook : Playbook name, e.g. bootstrap} {--server=* : Server name (repeatable)} {--all : Run against every server} {--check : Run in check mode (dry run)} {--diff : Show file diffs}')]
#[Description('Run an Ansible playbook against the given servers')]
class AnsibleRunCommand extends Command
{
    use ResolvesServers;

    public function handle(AnsibleRunner $runner): int
    {
        /** @var array<int, string> $names */
        $names = $this->option('server');
        $all = (bool) $this->option('all');

        if ($all === (count($names) > 0)) {
            $this->components->error('Specify one or more --server options, or --all (but not both).');

            return self::FAILURE;
        }

        $servers = $this->resolveServers($names, 'Unable to run Ansible.');

        if ($servers === null) {
            return self::FAILURE;
        }

        if ($servers->isEmpty()) {
            $this->components->error('Unable to run Ansible: no servers are registered.');

            return self::FAILURE;
        }

        try {
            $result = $runner->run(
                $this->argument('playbook'),
                $servers,
                check: (bool) $this->option('check'),
                diff: (bool) $this->option('diff'),
                onOutput: fn (string $type, string $chunk) => $this->output->write($chunk),
            );
        } catch (InvalidArgumentException|RuntimeException|ProcessTimedOutException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $outcome = $result->outcome();
        $summary = "{$outcome->label()} (exit code {$result->exitCode}).";

        $this->newLine();

        if ($outcome === AnsibleRunOutcome::Success) {
            $this->components->info($summary);
        } else {
            $this->components->error($summary);
        }

        return $result->exitCode;
    }
}
