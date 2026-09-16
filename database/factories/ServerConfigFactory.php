<?php

namespace Database\Factories;

use App\Models\Server;
use App\Models\ServerConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerConfig>
 */
class ServerConfigFactory extends Factory
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
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->sentence(),
        ];
    }
}
