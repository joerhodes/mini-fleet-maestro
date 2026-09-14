<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerConfig extends Model
{
    /** @use HasFactory<\Database\Factories\ServerConfigFactory> */
    use HasFactory;

    protected $fillable = ['server_id', 'key', 'value'];

    protected $casts = [
        'value' => 'encrypted',
    ];

    /**
     * Get the server that this config belongs to.
     * @return BelongsTo<Server,ServerConfig>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
