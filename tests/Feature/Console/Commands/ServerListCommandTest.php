<?php

use App\Models\Server;

test('lists registered servers', function () {
    Server::factory()->create([
        'name' => 'msv05',
        'hostname' => 'msv05.local',
        'ssh_user' => 'admin',
        'ssh_port' => 22,
        'status' => 'connected',
    ]);

    $this->artisan('server:list')
        ->expectsTable(
            ['ID', 'Name', 'Hostname', 'SSH User', 'SSH Port', 'Status'],
            [
                [1, 'msv05', 'msv05.local', 'admin', 22, 'Connected'],
            ],
        )
        ->assertExitCode(0);
});

test('reports when no servers are registered', function () {
    $this->artisan('server:list')
        ->expectsOutputToContain('No servers registered.')
        ->assertExitCode(0);
});
