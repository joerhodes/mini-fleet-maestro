<?php

use App\Actions\Servers\TestServerConnectivity;
use App\Enums\ServerStatus;
use App\Models\Role;
use App\Models\Server;
use App\Models\ServerConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('each server has a Delete button', function () {
    Server::factory()->count(2)->create();

    Livewire::test('servers.server-list')->assertSeeInOrder(['Test', 'Edit', 'Delete', 'Test', 'Edit', 'Delete']);
});

test('the confirm modal names the server and documents what will be removed', function () {
    $server = Server::factory()->create(['name' => 'mini01']);
    $server->roles()->attach(Role::factory()->count(2)->create()->pluck('role'));
    ServerConfig::factory()->create(['server_id' => $server->id]);

    Livewire::test('servers.server-list')
        ->call('confirmDelete', $server->id)
        ->assertSet('deletingId', $server->id)
        ->assertSee('mini01')
        ->assertSee('detached from all roles')
        ->assertSeeText('(2)')
        ->assertSee('all of its configuration')
        ->assertSeeText('(1)');

    expect(Server::count())->toBe(1);
});

test('confirming deletes the server, detaches its roles and deletes its configs', function () {
    $server = Server::factory()->create(['name' => 'mini01']);
    $keep = Server::factory()->create(['name' => 'mini02']);
    $role = Role::factory()->create();
    $server->roles()->attach($role->role);
    $keep->roles()->attach($role->role);
    ServerConfig::factory()->create(['server_id' => $server->id]);
    $keepConfig = ServerConfig::factory()->create(['server_id' => $keep->id]);

    Livewire::test('servers.server-list')
        ->call('confirmDelete', $server->id)
        ->call('delete')
        ->assertSet('deletingId', null)
        ->assertDontSee('mini01')
        ->assertSee('mini02');

    expect(Server::pluck('name')->all())->toBe(['mini02'])
        ->and(ServerConfig::pluck('id')->all())->toBe([$keepConfig->id])
        ->and(Role::find($role->role))->not->toBeNull()
        ->and($keep->roles()->count())->toBe(1)
        ->and(DB::table('server_roles')->where('server_id', $server->id)->count())->toBe(0);
});

test('cancelling leaves nothing deleted', function () {
    $server = Server::factory()->create();

    Livewire::test('servers.server-list')->call('confirmDelete', $server->id);

    expect(Server::count())->toBe(1);
});

test('delete without a confirmation target does nothing', function () {
    Server::factory()->create();

    Livewire::test('servers.server-list')->call('delete');

    expect(Server::count())->toBe(1);
});

test('the delete target cannot be tampered with', function () {
    Livewire::test('servers.server-list')->set('deletingId', 1);
})->throws(Exception::class, 'Cannot update locked property');

test('an unknown server cannot be selected for deletion', function () {
    Livewire::test('servers.server-list')->call('confirmDelete', 999);
})->throws(ModelNotFoundException::class);

test('the Test All button is shown', function () {
    Server::factory()->create();

    Livewire::test('servers.server-list')->assertSee('Test All');
});

test('testing one server runs the connectivity test for only that server', function () {
    $server = Server::factory()->create(['name' => 'mini01']);
    Server::factory()->create(['name' => 'mini02']);
    $this->mock(TestServerConnectivity::class)
        ->shouldReceive('__invoke')
        ->once()
        ->withArgs(fn (Server $tested) => $tested->is($server))
        ->andReturnUsing(function (Server $tested) {
            $tested->update(['status' => ServerStatus::Ready]);

            return $tested;
        });

    Livewire::test('servers.server-list')
        ->call('test', $server->id)
        ->assertSee('Ready');
});

test('testing all runs the connectivity test for every server', function () {
    Server::factory()->count(3)->create();
    $this->mock(TestServerConnectivity::class)->shouldReceive('__invoke')->times(3)->andReturnArg(0);

    Livewire::test('servers.server-list')->call('testAll');
});

test('an unknown server cannot be tested', function () {
    Livewire::test('servers.server-list')->call('test', 999);
})->throws(ModelNotFoundException::class);
