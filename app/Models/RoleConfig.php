<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleConfig extends Model
{
    /** @use HasFactory<\Database\Factories\RoleConfigFactory> */
    use HasFactory;

    protected $fillable = ['role', 'key', 'value'];

    protected $casts = [
        'value' => 'encrypted',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
