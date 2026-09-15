<?php

namespace App\Models;

use App\Enums\ServerStatus;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'hostname',
        'ssh_port',
        'ssh_user',
        'status',
        'last_checked_at',
        'last_check_output',
        'notes',
    ];

    protected $casts = [
        'status' => ServerStatus::class,
        'last_checked_at' => 'datetime',
    ];

    /**
     * Get the secrets for this server.
     *
     * @return HasMany<ServerConfig,Server>
     */
    public function secrets(): HasMany
    {
        return $this->hasMany(ServerConfig::class);
    }

    public function roles(): HasManyThrough
    {
        return $this->hasManyThrough(ServerRole::class, Role::class);
    }
}
