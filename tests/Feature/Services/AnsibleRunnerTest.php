<?php

use App\Models\Server;
use App\Models\ServerConfig;
use App\Services\AnsibleRunner;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->inventoryDirectory = sys_get_temp_dir().'/ansible-runner-test-'.uniqid();
    config([
        'ansible.paths.inventory' => $this->inventoryDirectory,
        'ansible.binary' => 'ansible-playbook',
        'ansible.timeout' => 1234,
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->inventoryDirectory);
});

function inventoryFiles(): array
{
    return File::isDirectory(config('ansible.paths.inventory'))
        ? File::files(config('ansible.paths.inventory'))
        : [];
}

test('runs ansible-playbook with the expected command, working directory, timeout and env', function () {
    Process::fake();
    $servers = collect([Server::factory()->create(['name' => 'msv05'])]);

    app(AnsibleRunner::class)->run('bootstrap', $servers);

    Process::assertRan(function (PendingProcess $process) {
        return $process->command[0] === 'ansible-playbook'
            && $process->command[1] === '-i'
            && preg_match('#/inventory-[0-9a-f-]{36}\.json$#', $process->command[2])
            && $process->command[3] === resource_path('ansible/playbooks/bootstrap.yml')
            && array_slice($process->command, 4) === ['--limit', 'msv05']
            && $process->path === resource_path('ansible')
            && $process->timeout === 1234
            && $process->environment === ['ANSIBLE_FORCE_COLOR' => '0'];
    });
});

test('limits the run to exactly the selected server names', function () {
    Process::fake();
    Server::factory()->create(['name' => 'msv07']);
    $servers = collect([
        Server::factory()->create(['name' => 'msv05']),
        Server::factory()->create(['name' => 'msv06']),
    ]);

    app(AnsibleRunner::class)->run('bootstrap', $servers);

    Process::assertRan(fn (PendingProcess $process) => array_slice($process->command, 4) === ['--limit', 'msv05,msv06']);
});

test('adds --check and --diff only when requested', function (bool $check, bool $diff, array $expectedFlags) {
    Process::fake();
    $servers = collect([Server::factory()->create(['name' => 'msv05'])]);

    app(AnsibleRunner::class)->run('bootstrap', $servers, check: $check, diff: $diff);

    Process::assertRan(fn (PendingProcess $process) => array_slice($process->command, 6) === $expectedFlags);
})->with([
    'neither' => [false, false, []],
    'check' => [true, false, ['--check']],
    'diff' => [false, true, ['--diff']],
    'both' => [true, true, ['--check', '--diff']],
]);

test('writes the inventory with 0600 permissions while the process runs', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'rig_id', 'value' => 'secret-rig']);
    $seen = [];
    Process::fake(function (PendingProcess $process) use (&$seen) {
        $path = $process->command[2];
        $seen = [
            'permissions' => substr(sprintf('%o', fileperms($path)), -4),
            'inventory' => json_decode(file_get_contents($path), true),
        ];

        return Process::result();
    });

    app(AnsibleRunner::class)->run('bootstrap', collect([$server->load(['roles.roleConfigs', 'secrets'])]));

    expect($seen['permissions'])->toBe('0600');
    expect($seen['inventory']['all']['hosts']['msv05']['rig_id'])->toBe('secret-rig');
});

test('deletes the inventory file after a successful run', function () {
    Process::fake();

    app(AnsibleRunner::class)->run('bootstrap', collect([Server::factory()->create()]));

    expect(inventoryFiles())->toBeEmpty();
});

test('deletes the inventory file after a nonzero exit', function () {
    Process::fake(['*' => Process::result(exitCode: 2)]);

    app(AnsibleRunner::class)->run('bootstrap', collect([Server::factory()->create()]));

    expect(inventoryFiles())->toBeEmpty();
});

test('deletes the inventory file when the process throws', function () {
    Process::fake(function () {
        throw new RuntimeException('The process exceeded the timeout.');
    });

    expect(fn () => app(AnsibleRunner::class)->run('bootstrap', collect([Server::factory()->create()])))
        ->toThrow(RuntimeException::class, 'The process exceeded the timeout.');
    expect(inventoryFiles())->toBeEmpty();
});

test('returns the exit code and output of the process', function () {
    Process::fake(['*' => Process::result(output: 'PLAY RECAP', errorOutput: 'warning', exitCode: 4)]);

    $result = app(AnsibleRunner::class)->run('bootstrap', collect([Server::factory()->create()]));

    expect($result->exitCode)->toBe(4);
    expect($result->output)->toContain('PLAY RECAP');
    expect($result->errorOutput)->toContain('warning');
});

test('streams output chunks to the callback as the process runs', function () {
    config(['ansible.binary' => '/bin/echo']);
    $chunks = [];

    $result = app(AnsibleRunner::class)->run(
        'bootstrap',
        collect([Server::factory()->create(['name' => 'msv05'])]),
        onOutput: function (string $type, string $chunk) use (&$chunks) {
            $chunks[] = [$type, $chunk];
        },
    );

    expect($chunks)->not->toBeEmpty();
    expect($chunks[0][0])->toBe('out');
    expect(implode('', array_column($chunks, 1)))->toBe($result->output)->toContain('--limit msv05');
});

test('rejects playbook names that are missing or attempt traversal', function (string $playbook) {
    Process::fake();

    expect(fn () => app(AnsibleRunner::class)->run($playbook, collect([Server::factory()->create()])))
        ->toThrow(InvalidArgumentException::class);
    Process::assertNothingRan();
    expect(inventoryFiles())->toBeEmpty();
})->with([
    'missing' => 'does-not-exist',
    'parent directory' => '../ansible.cfg',
    'nested traversal' => 'playbooks/../playbooks/bootstrap',
    'absolute path' => '/etc/passwd',
    'extension included' => 'bootstrap.yml',
    'empty' => '',
]);

test('rejects an empty server collection', function () {
    Process::fake();

    expect(fn () => app(AnsibleRunner::class)->run('bootstrap', collect()))
        ->toThrow(InvalidArgumentException::class);
    Process::assertNothingRan();
});
