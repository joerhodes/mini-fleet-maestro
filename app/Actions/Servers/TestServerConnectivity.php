<?php

namespace App\Actions\Servers;

use App\Enums\ServerStatus;
use App\Models\Server;
use App\Services\AnsibleRunner;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;

class TestServerConnectivity
{
    public function __construct(protected AnsibleRunner $ansibleRunner) {}

    /**
     * Step a server through the ping -> SSH -> Ansible connectivity ladder, stopping at the
     * first rung that fails, and persist the resulting status and diagnostic output.
     */
    public function __invoke(Server $server): Server
    {
        [$status, $output] = $this->ping($server)
            ?? $this->ssh($server)
            ?? $this->ansiblePing($server)
            ?? [ServerStatus::Ready, 'Ansible ping succeeded.'];

        $server->fill([
            'status' => $status,
            'last_checked_at' => now(),
            'last_check_output' => $output,
        ])->save();

        return $server;
    }

    /**
     * @return array{0: ServerStatus, 1: string}|null Null when the ping succeeds (continue the ladder).
     */
    protected function ping(Server $server): ?array
    {
        $timeout = config('connectivity.ping_timeout');
        $flag = PHP_OS_FAMILY === 'Darwin' ? '-t' : '-W';

        try {
            $result = Process::timeout($timeout + 2)
                ->run(['ping', '-c', '1', $flag, (string) $timeout, $server->hostname]);
        } catch (ProcessTimedOutException) {
            return [ServerStatus::Unreachable, "Ping timed out after {$timeout}s."];
        }

        if ($result->successful()) {
            return null;
        }

        return [ServerStatus::Unreachable, $this->describe($result, 'Ping failed.')];
    }

    /**
     * @return array{0: ServerStatus, 1: string}|null Null when SSH succeeds (continue the ladder).
     */
    protected function ssh(Server $server): ?array
    {
        $timeout = config('connectivity.ssh_timeout');

        try {
            $result = Process::timeout($timeout + 2)->run([
                'ssh',
                '-o', 'BatchMode=yes',
                '-o', "ConnectTimeout={$timeout}",
                '-p', (string) $server->ssh_port,
                "{$server->ssh_user}@{$server->hostname}",
                'exit',
            ]);
        } catch (ProcessTimedOutException) {
            return [ServerStatus::PingOk, "SSH connection timed out after {$timeout}s."];
        }

        if ($result->successful()) {
            return null;
        }

        return [ServerStatus::PingOk, $this->describe($result, 'SSH connection failed.')];
    }

    /**
     * @return array{0: ServerStatus, 1: string}|null Null when the Ansible ping succeeds.
     */
    protected function ansiblePing(Server $server): ?array
    {
        try {
            $result = $this->ansibleRunner->run(
                'ping',
                collect([$server]),
                connectionTimeout: config('connectivity.ansible_timeout'),
            );
        } catch (ProcessTimedOutException $exception) {
            return [ServerStatus::SshOk, $exception->getMessage()];
        }

        if ($result->exitCode === 0) {
            return null;
        }

        return [ServerStatus::SshOk, trim($result->output."\n".$result->errorOutput) ?: 'Ansible ping failed.'];
    }

    protected function describe(ProcessResult $result, string $fallback): string
    {
        return trim($result->output()."\n".$result->errorOutput()) ?: $fallback;
    }
}
