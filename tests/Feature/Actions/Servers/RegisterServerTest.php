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

test('returns the existing server without creating a duplicate when already registered', function () {
    Server::factory()->create(['name' => 'msv05', 'hostname' => 'original.local', 'ssh_user' => 'original', 'ssh_port' => 22]);
    $registerServer = new RegisterServer;

    $server = $registerServer('msv05', 'other.local', 'other', 2222);

    expect(Server::where('name', 'msv05')->count())->toBe(1);
    expect($server->hostname)->toBe('original.local');
    expect($server->ssh_user)->toBe('original');
    expect($server->ssh_port)->toBe(22);
});
