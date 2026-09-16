<?php

use App\Models\Server;
use App\Models\ServerConfig;

test('sets a config value for a registered server', function () {
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('server:config', ['server' => 'msv05', 'key' => 'rig_id', 'value' => 'rig-42'])
        ->expectsOutputToContain('Set config [rig_id] for server [msv05].')
        ->assertExitCode(0);

    $server = Server::where('name', 'msv05')->first();
    $this->assertDatabaseHas('server_configs', ['server_id' => $server->id, 'key' => 'rig_id']);
    expect(ServerConfig::where('server_id', $server->id)->where('key', 'rig_id')->first()->value)->toBe('rig-42');
});

test('updates an existing config value instead of duplicating it', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'rig_id', 'value' => 'rig-42']);

    $this->artisan('server:config', ['server' => 'msv05', 'key' => 'rig_id', 'value' => 'rig-99'])
        ->assertExitCode(0);

    expect(ServerConfig::where('server_id', $server->id)->where('key', 'rig_id')->count())->toBe(1);
    expect(ServerConfig::where('server_id', $server->id)->where('key', 'rig_id')->first()->value)->toBe('rig-99');
});

test('rejects a server that was not found', function () {
    $this->artisan('server:config', ['server' => 'unknown-server', 'key' => 'rig_id', 'value' => 'rig-42'])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('server_configs', ['key' => 'rig_id']);
});

test('rejects a blank key', function () {
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('server:config', ['server' => 'msv05', 'key' => '', 'value' => 'rig-42'])
        ->assertExitCode(1);

    $this->assertDatabaseCount('server_configs', 0);
});
