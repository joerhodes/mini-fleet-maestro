<?php

use App\Models\Server;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

test('roles returns a HasManyThrough relation', function () {
    $server = new Server;

    expect($server->roles())->toBeInstanceOf(HasManyThrough::class);
});
