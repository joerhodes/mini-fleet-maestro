<?php

use App\Models\Server;
use App\Models\ServerConfig;

test('lists the configuration values stored for a server', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'pool_url', 'value' => 'pool.example.com']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'rig_id', 'value' => 'rig-42']);

    $this->artisan('server:config-list', ['server' => 'msv05'])
        ->expectsTable(
            ['Key', 'Value'],
            [
                ['pool_url', 'pool.example.com'],
                ['rig_id', 'rig-42'],
            ],
        )
        ->assertExitCode(0);
});

test('reports when a server has no configuration', function () {
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('server:config-list', ['server' => 'msv05'])
        ->expectsOutputToContain('No configuration found for server [msv05].')
        ->assertExitCode(0);
});

test('rejects a server that was not found', function () {
    $this->artisan('server:config-list', ['server' => 'unknown-server'])
        ->assertExitCode(1);
});
