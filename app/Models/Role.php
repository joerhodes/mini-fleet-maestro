<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $primaryKey = 'role';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['role', 'label', 'always_apply'];

    public function roleConfigs(): HasMany
    {
        return $this->hasMany(RoleConfig::class, 'role', 'role');
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class, 'server_roles', 'role', 'server_id');
    }
}
