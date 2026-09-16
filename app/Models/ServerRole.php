<?php

namespace App\Models;

use Database\Factories\ServerRoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerRole extends Model
{
    /** @use HasFactory<ServerRoleFactory> */
    use HasFactory;

    protected $fillable = ['server_id', 'role'];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'role');
    }
}
