<?php

use App\Actions\Servers\SetServerConfig;
use App\Models\Server;
use App\Models\ServerConfig;

test('creates a new config value for a server', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    $setServerConfig = new SetServerConfig;

    $config = $setServerConfig($server, 'rig_id', 'rig-42');

    expect($config)->toBeInstanceOf(ServerConfig::class);
    $this->assertDatabaseHas('server_configs', [
        'server_id' => $server->id,
        'key' => 'rig_id',
    ]);
    expect($config->fresh()->value)->toBe('rig-42');
});

test('updates the value of an existing config for the same server and key', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'rig_id', 'value' => 'rig-42']);
    $setServerConfig = new SetServerConfig;

    $config = $setServerConfig($server, 'rig_id', 'rig-99');

    expect(ServerConfig::where('server_id', $server->id)->where('key', 'rig_id')->count())->toBe(1);
    expect($config->fresh()->value)->toBe('rig-99');
});
