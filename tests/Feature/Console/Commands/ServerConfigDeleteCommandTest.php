<?php

use App\Models\Server;
use App\Models\ServerConfig;

test('deletes a config value for a registered server', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'rig_id', 'value' => 'rig-42']);

    $this->artisan('server:config-delete', ['server' => 'msv05', 'key' => 'rig_id'])
        ->expectsOutputToContain('Deleted config [rig_id] for server [msv05].')
        ->assertExitCode(0);

    $this->assertDatabaseMissing('server_configs', ['server_id' => $server->id, 'key' => 'rig_id']);
});

test('rejects a server that was not found', function () {
    $this->artisan('server:config-delete', ['server' => 'unknown-server', 'key' => 'rig_id'])
        ->assertExitCode(1);
});

test('fails when no matching config exists for the server', function () {
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('server:config-delete', ['server' => 'msv05', 'key' => 'rig_id'])
        ->expectsOutputToContain('No config [rig_id] found for server [msv05].')
        ->assertExitCode(1);
});

test('rejects a blank key', function () {
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('server:config-delete', ['server' => 'msv05', 'key' => ''])
        ->assertExitCode(1);
});
