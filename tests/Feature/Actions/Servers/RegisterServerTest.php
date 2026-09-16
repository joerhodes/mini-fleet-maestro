<?php

use App\Actions\Servers\RegisterServer;
use App\Enums\ServerStatus;
use App\Models\Server;

test('creates a server with the given attributes and a pending status', function () {
    $registerServer = new RegisterServer;

    $server = $registerServer('msv05', 'msv05.local', 'admin', 2222);

    expect($server)->toBeInstanceOf(Server::class);
    expect($server->status)->toBe(ServerStatus::Pending);
    $this->assertDatabaseHas('servers', [
        'name' => 'msv05',
        'hostname' => 'msv05.local',
        'ssh_user' => 'admin',
        'ssh_port' => 2222,
        'status' => 'pending',
    ]);
});

test('defaults ssh_port to 22 when not given', function () {
    $registerServer = new RegisterServer;

    $server = $registerServer('msv05', 'msv05.local', 'admin');

    expect($server->ssh_port)->toBe(22);
});
