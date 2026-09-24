<?php

use App\Actions\Ansible\BuildServerVars;
use App\Models\Role;
use App\Models\RoleConfig;
use App\Models\Server;
use App\Models\ServerConfig;
use App\Models\ServerRole;

test('returns only always_apply role vars when the server has no assigned roles', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $vars = (new BuildServerVars)->handle($server);

    expect($vars)->toBe([
        'ntp_server' => 'time.apple.com',
        'app_roles' => ['common'],
    ]);
});

test('includes role_config values for an explicitly assigned role', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    RoleConfig::factory()->create(['role' => 'xmrig', 'key' => 'pool_url', 'value' => 'pool.example.com']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);

    $vars = (new BuildServerVars)->handle($server);

    expect($vars)->toBe([
        'pool_url' => 'pool.example.com',
        'app_roles' => ['xmrig'],
    ]);
});

test('includes an always_apply role config even without an explicit server_role assignment', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $vars = (new BuildServerVars)->handle($server);

    $this->assertDatabaseMissing('server_roles', ['server_id' => $server->id, 'role' => 'common']);
    expect($vars['ntp_server'])->toBe('time.apple.com');
    expect($vars['app_roles'])->toContain('common');
});

test('lets server_config override a role_config value and records the overridden key', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    RoleConfig::factory()->create(['role' => 'xmrig', 'key' => 'pool_url', 'value' => 'pool.example.com']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'pool_url', 'value' => 'override.example.com']);

    $action = new BuildServerVars();
    $vars = $action->handle($server);

    expect($vars['pool_url'])->toBe('override.example.com');
    expect($action->overriddenKeys)->toBe(['pool_url']);
});

test('does not throw when two roles define the same role_config key with the same value', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'role-a', 'always_apply' => false]);
    Role::factory()->create(['role' => 'role-b', 'always_apply' => false]);
    RoleConfig::factory()->create(['role' => 'role-a', 'key' => 'shared_key', 'value' => 'same']);
    RoleConfig::factory()->create(['role' => 'role-b', 'key' => 'shared_key', 'value' => 'same']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'role-a']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'role-b']);

    $vars = (new BuildServerVars)->handle($server);

    expect($vars['shared_key'])->toBe('same');
});

test('throws when two roles define the same role_config key with different values', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'role-a', 'always_apply' => false]);
    Role::factory()->create(['role' => 'role-b', 'always_apply' => false]);
    RoleConfig::factory()->create(['role' => 'role-a', 'key' => 'shared_key', 'value' => 'one']);
    RoleConfig::factory()->create(['role' => 'role-b', 'key' => 'shared_key', 'value' => 'two']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'role-a']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'role-b']);

    (new BuildServerVars)->handle($server);
})->throws(RuntimeException::class, 'shared_key');

test('includes every applicable role name in app_roles', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);

    $vars = (new BuildServerVars)->handle($server);

    expect($vars['app_roles'])->toBe(['common', 'xmrig']);
});
