<?php

use App\Models\Server;
use App\Services\AnsibleRunner;
use App\Support\Ansible\AnsibleRunResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

test('fails without --server or --all', function () {
    Process::fake();
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('ansible:run', ['playbook' => 'bootstrap'])
        ->expectsOutputToContain('Specify one or more --server options, or --all')
        ->assertExitCode(1);

    Process::assertNothingRan();
});

test('fails when both --server and --all are given', function () {
    Process::fake();
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('ansible:run', ['playbook' => 'bootstrap', '--server' => ['msv05'], '--all' => true])
        ->expectsOutputToContain('Specify one or more --server options, or --all')
        ->assertExitCode(1);

    Process::assertNothingRan();
});

test('fails and lists every unknown server name', function () {
    Process::fake();
    Server::factory()->create(['name' => 'msv05']);

    $exitCode = Artisan::call('ansible:run', ['playbook' => 'bootstrap', '--server' => ['msv05', 'msv99', 'typo']]);

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('[msv99]', '[typo]');
    Process::assertNothingRan();
});

test('fails with --all when no servers are registered', function () {
    Process::fake();

    $this->artisan('ansible:run', ['playbook' => 'bootstrap', '--all' => true])
        ->expectsOutputToContain('no servers are registered')
        ->assertExitCode(1);

    Process::assertNothingRan();
});

test('fails when the playbook does not exist', function () {
    Process::fake();
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('ansible:run', ['playbook' => 'nope', '--server' => ['msv05']])
        ->expectsOutputToContain('The playbook [nope] was not found.')
        ->assertExitCode(1);

    Process::assertNothingRan();
});

test('runs against only the named servers with check and diff', function () {
    Process::fake();
    Server::factory()->create(['name' => 'msv05']);
    Server::factory()->create(['name' => 'msv06']);

    $this->artisan('ansible:run', ['playbook' => 'bootstrap', '--server' => ['msv06'], '--check' => true, '--diff' => true])
        ->assertExitCode(0);

    Process::assertRan(fn (PendingProcess $process) => array_slice($process->command, 4) === ['--limit', 'msv06', '--check', '--diff']);
});

test('runs against every server with --all', function () {
    Process::fake();
    Server::factory()->create(['name' => 'msv05']);
    Server::factory()->create(['name' => 'msv06']);

    $this->artisan('ansible:run', ['playbook' => 'bootstrap', '--all' => true])
        ->assertExitCode(0);

    Process::assertRan(fn (PendingProcess $process) => array_slice($process->command, 4) === ['--limit', 'msv05,msv06']);
});

test('streams runner output live and returns the ansible exit code with an outcome summary', function () {
    Server::factory()->create(['name' => 'msv05']);
    $this->mock(AnsibleRunner::class)
        ->shouldReceive('run')
        ->andReturnUsing(function ($playbook, $servers, $check, $diff, $onOutput) {
            $onOutput('out', 'TASK [Gather facts]');
            $onOutput('out', 'fatal: unreachable');

            return new AnsibleRunResult(exitCode: 4, output: '', errorOutput: '');
        });

    $exitCode = Artisan::call('ansible:run', ['playbook' => 'bootstrap', '--server' => ['msv05']]);

    expect($exitCode)->toBe(4);
    expect(Artisan::output())->toContain(
        'TASK [Gather facts]fatal: unreachable',
        'One or more hosts were unreachable (exit code 4).',
    );
});

test('fails when the runner throws', function () {
    Server::factory()->create(['name' => 'msv05']);
    $this->mock(AnsibleRunner::class)
        ->shouldReceive('run')
        ->andThrow(new RuntimeException('Conflicting values for [ntp_server].'));

    $this->artisan('ansible:run', ['playbook' => 'bootstrap', '--server' => ['msv05']])
        ->expectsOutputToContain('Conflicting values for [ntp_server].')
        ->assertExitCode(1);
});
