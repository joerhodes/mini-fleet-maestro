<?php

use App\Actions\Servers\DeleteServerConfig;
use App\Models\Server;
use App\Models\ServerConfig;

test('deletes an existing config value for a server', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'rig_id', 'value' => 'rig-42']);
    $deleteServerConfig = new DeleteServerConfig;

    $deleted = $deleteServerConfig($server, 'rig_id');

    expect($deleted)->toBeTrue();
    $this->assertDatabaseMissing('server_configs', ['server_id' => $server->id, 'key' => 'rig_id']);
});

test('returns false when no matching config exists', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    $deleteServerConfig = new DeleteServerConfig;

    $deleted = $deleteServerConfig($server, 'rig_id');

    expect($deleted)->toBeFalse();
});
