<?php

use App\Actions\Servers\TestServerConnectivity;
use App\Enums\ServerStatus;
use App\Models\Server;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->connectivityTest = $this->mock(TestServerConnectivity::class);
    $this->connectivityTest->shouldReceive('__invoke')->andReturnArg(0)->byDefault();
});

test('a new server starts with default values', function () {
    Livewire::test('servers.server-form')
        ->assertSet('form.serverId', null)
        ->assertSet('form.sshPort', '22')
        ->assertSee('Add server');
});

test('an existing server loads its attributes', function () {
    $server = Server::factory()->create([
        'name' => 'mini01',
        'hostname' => 'mini01.invalid',
        'ssh_user' => 'admin',
        'ssh_port' => 2222,
        'notes' => 'Rack 2',
    ]);

    Livewire::test('servers.server-form', ['server' => $server])
        ->assertSet('form.serverId', $server->id)
        ->assertSet('form.name', 'mini01')
        ->assertSet('form.hostname', 'mini01.invalid')
        ->assertSet('form.sshUser', 'admin')
        ->assertSet('form.sshPort', '2222')
        ->assertSet('form.notes', 'Rack 2')
        ->assertSee('Edit server');
});

test('saving a new server creates it as pending and redirects to its page', function () {
    Livewire::test('servers.server-form')
        ->set('form.name', 'mini02')
        ->set('form.hostname', 'mini02.invalid')
        ->set('form.sshUser', 'admin')
        ->set('form.sshPort', '2200')
        ->call('save')
        ->assertHasNoErrors();

    $server = Server::firstWhere('name', 'mini02');

    expect($server)->not->toBeNull()
        ->and($server->ssh_port)->toBe(2200)
        ->and($server->status)->toBe(ServerStatus::Pending)
        ->and($server->notes)->toBeNull();
});

test('a new server save redirects to the edit page', function () {
    Livewire::test('servers.server-form')
        ->set('form.name', 'mini02')
        ->set('form.hostname', 'mini02.invalid')
        ->set('form.sshUser', 'admin')
        ->call('save')
        ->assertRedirect(route('servers.edit', Server::firstWhere('name', 'mini02')));
});

test('saving an existing server updates it without creating another', function () {
    $server = Server::factory()->create(['name' => 'mini01']);

    Livewire::test('servers.server-form', ['server' => $server])
        ->set('form.name', 'mini01-renamed')
        ->set('form.notes', 'Moved')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect(Server::count())->toBe(1)
        ->and($server->fresh()->name)->toBe('mini01-renamed')
        ->and($server->fresh()->notes)->toBe('Moved');
});

test('changing a connection field resets the status', function () {
    $server = Server::factory()->create([
        'status' => ServerStatus::Ready,
        'last_checked_at' => now(),
        'last_check_output' => 'ok',
    ]);

    Livewire::test('servers.server-form', ['server' => $server])
        ->set('form.hostname', 'other.invalid')
        ->call('save');

    $server->refresh();

    expect($server->status)->toBe(ServerStatus::Pending)
        ->and($server->last_checked_at)->toBeNull()
        ->and($server->last_check_output)->toBeNull();
});

test('saving a server that is left pending runs the connectivity test', function () {
    $server = Server::factory()->create(['status' => ServerStatus::Ready]);
    $this->connectivityTest->shouldReceive('__invoke')->once()->andReturnUsing(function (Server $tested) {
        $tested->update(['status' => ServerStatus::SshOk]);

        return $tested;
    });

    Livewire::test('servers.server-form', ['server' => $server])
        ->set('form.sshPort', '2222')
        ->call('save')
        ->assertHasNoErrors();

    expect($server->fresh()->status)->toBe(ServerStatus::SshOk);
});

test('saving a new server runs the connectivity test', function () {
    $this->connectivityTest->shouldReceive('__invoke')->once()->andReturnArg(0);

    Livewire::test('servers.server-form')
        ->set('form.name', 'mini02')
        ->set('form.hostname', 'mini02.invalid')
        ->set('form.sshUser', 'admin')
        ->call('save');
});

test('saving without a status reset does not run the connectivity test', function () {
    $server = Server::factory()->create(['status' => ServerStatus::Ready]);
    $this->connectivityTest->shouldNotReceive('__invoke');

    Livewire::test('servers.server-form', ['server' => $server])
        ->set('form.notes', 'note')
        ->call('save');
});

test('changing only the name or notes keeps the status', function () {
    $server = Server::factory()->create([
        'status' => ServerStatus::Ready,
        'last_checked_at' => now(),
        'last_check_output' => 'ok',
    ]);

    Livewire::test('servers.server-form', ['server' => $server])
        ->set('form.name', 'new-name')
        ->set('form.notes', 'note')
        ->call('save');

    $server->refresh();

    expect($server->status)->toBe(ServerStatus::Ready)
        ->and($server->last_checked_at)->not->toBeNull()
        ->and($server->last_check_output)->toBe('ok');
});

test('required fields and the port range are validated', function () {
    Livewire::test('servers.server-form')
        ->set('form.sshPort', '70000')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required', 'form.hostname' => 'required', 'form.sshUser' => 'required', 'form.sshPort' => 'between']);

    Livewire::test('servers.server-form')
        ->set('form.sshPort', 'abc')
        ->call('save')
        ->assertHasErrors(['form.sshPort' => 'integer']);

    expect(Server::count())->toBe(0);
});

test('the name must be unique but a server can keep its own name', function () {
    Server::factory()->create(['name' => 'mini01']);
    $other = Server::factory()->create(['name' => 'mini02']);

    Livewire::test('servers.server-form')
        ->set('form.name', 'mini01')
        ->set('form.hostname', 'x.invalid')
        ->set('form.sshUser', 'admin')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);

    Livewire::test('servers.server-form', ['server' => $other])
        ->call('save')
        ->assertHasNoErrors();
});

test('the server id cannot be tampered with', function () {
    Livewire::test('servers.server-form')->set('form.serverId', 1);
})->throws(Exception::class, 'Cannot update locked property');
