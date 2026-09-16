<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerRole>
 */
class ServerRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'role' => Role::factory(),
        ];
    }
}
