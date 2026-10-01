<?php

use App\Models\Server;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $server = Server::factory()->create();

    $this->get(route('servers.index'))->assertRedirect(route('login'));
    $this->get(route('servers.create'))->assertRedirect(route('login'));
    $this->get(route('servers.edit', $server))->assertRedirect(route('login'));
});

test('the server list shows each server with an Edit link and an Add Server link', function () {
    $server = Server::factory()->create(['name' => 'mini01', 'hostname' => 'mini01.invalid']);

    $this->actingAs(User::factory()->create())
        ->get(route('servers.index'))
        ->assertOk()
        ->assertSeeInOrder(['mini01', 'mini01.invalid', 'Pending', 'Edit'])
        ->assertSee(route('servers.edit', $server), false)
        ->assertSee(route('servers.create'), false)
        ->assertSee('Add Server');
});

test('the server list reports when there are no servers', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('servers.index'))
        ->assertOk()
        ->assertSee('No servers have been added yet.');
});

test('the sidebar links to the servers page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee(route('servers.index'), false);
});

test('the add page shows an empty form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('servers.create'))
        ->assertOk()
        ->assertSeeInOrder(['Add server', 'Configuration', 'Save this server before managing its configuration.']);
});

test('the edit page shows the server and its configuration section', function () {
    $server = Server::factory()->create(['name' => 'mini01']);

    $this->actingAs(User::factory()->create())
        ->get(route('servers.edit', $server))
        ->assertOk()
        ->assertSeeInOrder(['Edit server', 'mini01', 'Configuration', 'Add Configuration']);
});

test('the edit page is not found for an unknown server', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('servers.edit', 999))
        ->assertNotFound();
});
