<?php

namespace App\Models;

use App\Enums\ServerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    /** @use HasFactory<\Database\Factories\ServerFactory> */
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
}
