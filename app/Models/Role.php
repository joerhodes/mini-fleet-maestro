<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    protected $primaryKey = 'role';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['role', 'label'];

    public function roleConfigs(): HasMany
    {
        return $this->hasMany(RoleConfig::class);
    }

    public function servers(): HasManyThrough
    {
        return $this->hasManyThrough(ServerRole::class, Server::class);
    }
}
