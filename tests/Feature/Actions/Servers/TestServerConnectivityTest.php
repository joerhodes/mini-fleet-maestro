<?php

use App\Actions\Servers\TestServerConnectivity;
use App\Enums\ServerStatus;
use App\Models\Server;
use App\Services\AnsibleRunner;
use App\Support\Ansible\AnsibleRunResult;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyProcessTimedOutException;
use Symfony\Component\Process\Process as SymfonyProcess;

function fakePingAndSsh(int $pingExitCode, int $sshExitCode): void
{
    Process::fake(function (PendingProcess $process) use ($pingExitCode, $sshExitCode) {
        return match ($process->command[0]) {
            'ping' => Process::result(exitCode: $pingExitCode, errorOutput: 'ping output'),
            'ssh' => Process::result(exitCode: $sshExitCode, errorOutput: 'ssh output'),
        };
    });
}

function mockAnsiblePing(int $exitCode): void
{
    test()->mock(AnsibleRunner::class)
        ->shouldReceive('run')
        ->once()
        ->withArgs(fn ($playbook, $servers, $check, $diff, $onOutput, $connectionTimeout) => $playbook === 'ping'
            && $connectionTimeout === config('connectivity.ansible_timeout'))
        ->andReturn(new AnsibleRunResult(exitCode: $exitCode, output: 'ansible output', errorOutput: ''));
}

test('stops at unreachable when the ping fails, without attempting ssh or ansible', function () {
    fakePingAndSsh(pingExitCode: 1, sshExitCode: 0);
    $this->mock(AnsibleRunner::class)->shouldNotReceive('run');
    $server = Server::factory()->create(['status' => ServerStatus::Pending]);

    $result = app(TestServerConnectivity::class)($server);

    expect($result->status)->toBe(ServerStatus::Unreachable);
    expect($result->last_check_output)->toContain('ping output');
    Process::assertRan(fn (PendingProcess $process) => $process->command[0] === 'ping');
    Process::assertNotRan(fn (PendingProcess $process) => $process->command[0] === 'ssh');
});

test('stops at ping_ok when ping succeeds but ssh fails', function () {
    fakePingAndSsh(pingExitCode: 0, sshExitCode: 1);
    $this->mock(AnsibleRunner::class)->shouldNotReceive('run');
    $server = Server::factory()->create();

    $result = app(TestServerConnectivity::class)($server);

    expect($result->status)->toBe(ServerStatus::PingOk);
    expect($result->last_check_output)->toContain('ssh output');
});

test('stops at ssh_ok when ssh succeeds but the ansible ping fails', function () {
    fakePingAndSsh(pingExitCode: 0, sshExitCode: 0);
    mockAnsiblePing(exitCode: 4);
    $server = Server::factory()->create();

    $result = app(TestServerConnectivity::class)($server);

    expect($result->status)->toBe(ServerStatus::SshOk);
    expect($result->last_check_output)->toContain('ansible output');
});

test('reaches ready when the full ladder succeeds', function () {
    fakePingAndSsh(pingExitCode: 0, sshExitCode: 0);
    mockAnsiblePing(exitCode: 0);
    $server = Server::factory()->create();

    $result = app(TestServerConnectivity::class)($server);

    expect($result->status)->toBe(ServerStatus::Ready);
});

test('records last_checked_at and persists the result', function () {
    fakePingAndSsh(pingExitCode: 1, sshExitCode: 0);
    $server = Server::factory()->create(['last_checked_at' => null]);

    app(TestServerConnectivity::class)($server);

    $server->refresh();
    expect($server->status)->toBe(ServerStatus::Unreachable);
    expect($server->last_checked_at)->not->toBeNull();
    expect($server->last_check_output)->not->toBeNull();
});

test('uses a short, OS-appropriate timeout flag for ping', function () {
    fakePingAndSsh(pingExitCode: 0, sshExitCode: 0);
    mockAnsiblePing(exitCode: 0);
    $server = Server::factory()->create();
    $expectedFlag = PHP_OS_FAMILY === 'Darwin' ? '-t' : '-W';

    app(TestServerConnectivity::class)($server);

    Process::assertRan(function (PendingProcess $process) use ($expectedFlag) {
        return $process->command[0] === 'ping'
            && in_array($expectedFlag, $process->command, true)
            && in_array((string) config('connectivity.ping_timeout'), $process->command, true);
    });
});

test('uses BatchMode and a ConnectTimeout for ssh', function () {
    fakePingAndSsh(pingExitCode: 0, sshExitCode: 0);
    mockAnsiblePing(exitCode: 0);
    $server = Server::factory()->create(['ssh_user' => 'minion', 'hostname' => 'mini01.invalid', 'ssh_port' => 2222]);

    app(TestServerConnectivity::class)($server);

    Process::assertRan(function (PendingProcess $process) {
        return $process->command[0] === 'ssh'
            && in_array('BatchMode=yes', $process->command, true)
            && in_array('ConnectTimeout='.config('connectivity.ssh_timeout'), $process->command, true)
            && in_array('minion@mini01.invalid', $process->command, true)
            && in_array('2222', $process->command, true);
    });
});

test('treats a ping timeout as unreachable', function () {
    Process::fake(function (PendingProcess $process) {
        if ($process->command[0] === 'ping') {
            throw new ProcessTimedOutException(
                new SymfonyProcessTimedOutException(new SymfonyProcess(['true']), SymfonyProcessTimedOutException::TYPE_GENERAL),
                Process::result(),
            );
        }

        return Process::result();
    });
    $this->mock(AnsibleRunner::class)->shouldNotReceive('run');
    $server = Server::factory()->create();

    $result = app(TestServerConnectivity::class)($server);

    expect($result->status)->toBe(ServerStatus::Unreachable);
    expect($result->last_check_output)->toContain('timed out');
});
