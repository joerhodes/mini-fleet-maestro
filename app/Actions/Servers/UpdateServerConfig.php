<?php

namespace App\Actions\Servers;

use App\Models\ServerConfig;

class UpdateServerConfig
{
    public function __invoke(ServerConfig $config, string $key, string $value): ServerConfig
    {
        $config->update(['key' => $key, 'value' => $value]);

        return $config;
    }
}
