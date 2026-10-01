<?php

use Laravel\Fortify\Features;

test('Fortify registration feature is disabled', function () {
    $this->assertFalse(Features::enabled(Features::registration()));
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(404);
});
