<?php

use App\Actions\Ansible\BuildRoleVars;
use App\Models\Role;
use App\Models\RoleConfig;
use Illuminate\Support\Collection;

test('it builds role vars', function () {
    $role = Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $vars = (new BuildRoleVars)->handle(collect([$role]));

    expect($vars)->toBe([
        'ntp_server' => 'time.apple.com',
    ]);
});
