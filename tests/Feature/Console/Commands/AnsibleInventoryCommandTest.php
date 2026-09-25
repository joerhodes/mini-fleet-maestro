<?php

use App\Enums\ServerStatus;
use App\Models\Role;
use App\Models\RoleConfig;
use App\Models\Server;
use App\Models\ServerRole;
use Illuminate\Support\Facades\Artisan;

test('outputs an inventory of every server when no names are given', function () {
    Server::factory()->create(['name' => 'msv05']);
    Server::factory()->create(['name' => 'msv06']);
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $exitCode = Artisan::call('ansible:inventory');

    $inventory = json_decode(Artisan::output(), true);
    expect($exitCode)->toBe(0);
    expect(array_keys($inventory['all']['hosts']))->toBe(['msv05', 'msv06']);
    expect($inventory['all']['vars'])->toBe(['ntp_server' => 'time.apple.com']);
});

test('outputs only the named servers', function () {
    Server::factory()->create(['name' => 'msv05']);
    Server::factory()->create(['name' => 'msv06']);

    $exitCode = Artisan::call('ansible:inventory', ['servers' => ['msv06']]);

    expect($exitCode)->toBe(0);
    expect(array_keys(json_decode(Artisan::output(), true)['all']['hosts']))->toBe(['msv06']);
});

test('fails and lists every unknown name instead of skipping it', function () {
    Server::factory()->create(['name' => 'msv05']);

    $exitCode = Artisan::call('ansible:inventory', ['servers' => ['msv05', 'msv99', 'typo']]);

    $output = Artisan::output();
    expect($exitCode)->toBe(1);
    expect($output)->toContain('[msv99]', '[typo]');
    expect($output)->not->toContain('"hosts"');
});

test('fails when no servers are registered', function () {
    $this->artisan('ansible:inventory')
        ->expectsOutputToContain('no servers are registered')
        ->assertExitCode(1);
});

test('only emits groups for roles assigned to the selected servers', function () {
    $selected = Server::factory()->create(['name' => 'msv05']);
    $other = Server::factory()->create(['name' => 'msv06']);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    Role::factory()->create(['role' => 'other', 'always_apply' => false]);
    RoleConfig::factory()->create(['role' => 'other', 'key' => 'other_var', 'value' => 'x']);
    ServerRole::factory()->create(['server_id' => $selected->id, 'role' => 'xmrig']);
    ServerRole::factory()->create(['server_id' => $other->id, 'role' => 'other']);

    Artisan::call('ansible:inventory', ['servers' => ['msv05']]);

    $inventory = json_decode(Artisan::output(), true);
    expect(array_keys($inventory['all']['children']))->toBe(['xmrig']);
});

test('includes servers regardless of their status', function () {
    Server::factory()->create(['name' => 'msv05', 'status' => ServerStatus::Failed]);

    $exitCode = Artisan::call('ansible:inventory');

    expect($exitCode)->toBe(0);
    expect(array_keys(json_decode(Artisan::output(), true)['all']['hosts']))->toBe(['msv05']);
});

test('fails when two always_apply roles define conflicting config for the same key', function () {
    Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    Role::factory()->create(['role' => 'baseline', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'a']);
    RoleConfig::factory()->create(['role' => 'baseline', 'key' => 'ntp_server', 'value' => 'b']);

    $this->artisan('ansible:inventory')
        ->expectsOutputToContain('conflicting values')
        ->assertExitCode(1);
});
