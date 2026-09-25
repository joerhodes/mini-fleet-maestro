<?php

namespace App\Models;

use App\Enums\ServerStatus;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Get the roles  for this server.
     *
     * @return BelongsToMany<Role,Server>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'server_roles', 'server_id', 'role');
    }
}
