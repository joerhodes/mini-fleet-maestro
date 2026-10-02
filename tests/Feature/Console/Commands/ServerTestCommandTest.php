<?php

use App\Actions\Servers\TestServerConnectivity;
use App\Enums\ServerStatus;
use App\Models\Server;
use Illuminate\Support\Facades\Artisan;

test('rejects a server that was not found', function () {
    $this->mock(TestServerConnectivity::class)->shouldNotReceive('__invoke');

    $exitCode = Artisan::call('server:test', ['server' => 'unknown-server']);

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('Unable to test server.', '[unknown-server]');
});

test('reports success and exits 0 when the server is fully reachable', function () {
    $server = Server::factory()->create(['name' => 'msv05', 'status' => ServerStatus::Ready, 'last_check_output' => 'pong']);
    $this->mock(TestServerConnectivity::class)->shouldReceive('__invoke')->once()->andReturn($server);

    $exitCode = Artisan::call('server:test', ['server' => 'msv05']);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('Ready', 'pong', 'is fully reachable');
});

test('reports the stopping point and exits 1 when the ladder does not reach ready', function () {
    $server = Server::factory()->create(['name' => 'msv05', 'status' => ServerStatus::Unreachable, 'last_check_output' => 'ping failed']);
    $this->mock(TestServerConnectivity::class)->shouldReceive('__invoke')->once()->andReturn($server);

    $exitCode = Artisan::call('server:test', ['server' => 'msv05']);

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('Unreachable', 'ping failed', 'stopped at [Unreachable]');
});
