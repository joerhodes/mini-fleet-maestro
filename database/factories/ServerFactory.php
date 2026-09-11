<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'hostname' => $this->faker->domainName,
            'ssh_port' => 22,
            'ssh_user' => $this->faker->userName,
            'status' => 'pending',
            'last_checked_at' => null,
            'last_check_output' => null,
            'notes' => null,
        ];
    }
}
