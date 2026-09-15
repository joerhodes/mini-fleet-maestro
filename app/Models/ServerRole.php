<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerRole extends Model
{
    /** @use HasFactory<\Database\Factories\ServerRoleFactory> */
    use HasFactory;

    protected $fillable = ['server_id', 'role'];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
