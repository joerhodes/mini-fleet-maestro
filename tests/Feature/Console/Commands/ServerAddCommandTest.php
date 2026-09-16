<?php

use App\Models\Server;

test('adds a server with the default ssh port', function () {
    $this->artisan('server:add', ['name' => 'msv05', 'hostname' => 'msv05.local', 'ssh-user' => 'admin'])
        ->expectsOutputToContain('Added server [msv05].')
        ->assertExitCode(0);

    $this->assertDatabaseHas('servers', [
        'name' => 'msv05',
        'hostname' => 'msv05.local',
        'ssh_user' => 'admin',
        'ssh_port' => 22,
        'status' => 'pending',
    ]);
});

test('adds a server with a custom ssh port', function () {
    $this->artisan('server:add', ['name' => 'msv05', 'hostname' => 'msv05.local', 'ssh-user' => 'admin', '--ssh-port' => 2222])
        ->assertExitCode(0);

    $this->assertDatabaseHas('servers', ['name' => 'msv05', 'ssh_port' => 2222]);
});

test('rejects a duplicate server name', function () {
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('server:add', ['name' => 'msv05', 'hostname' => 'other.local', 'ssh-user' => 'admin'])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('servers', ['hostname' => 'other.local']);
});

test('rejects an ssh port outside the valid range', function () {
    $this->artisan('server:add', ['name' => 'msv05', 'hostname' => 'msv05.local', 'ssh-user' => 'admin', '--ssh-port' => 99999])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('servers', ['name' => 'msv05']);
});
