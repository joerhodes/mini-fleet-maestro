<?php

use App\Models\Role;
use App\Models\RoleConfig;
use App\Models\Server;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('outputs valid json vars to stdout by default', function () {
    Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $exitCode = Artisan::call('ansible:vars', ['server' => 'msv05']);

    expect($exitCode)->toBe(0);
    expect(json_decode(Artisan::output(), true))->toBe([
        'ntp_server' => 'time.apple.com',
        'app_roles' => ['common'],
    ]);
});

test('writes vars to the given file when --output is passed', function () {
    Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $path = sys_get_temp_dir().'/ansible-vars-test-'.uniqid().'.json';

    $this->artisan('ansible:vars', ['server' => 'msv05', '--output' => $path])
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
    expect(json_decode(File::get($path), true))->toBe([
        'ntp_server' => 'time.apple.com',
        'app_roles' => ['common'],
    ]);

    File::delete($path);
});

test('rejects an unknown server', function () {
    $this->artisan('ansible:vars', ['server' => 'unknown-server'])
        ->assertExitCode(1);
});
