<?php

namespace Pterodactyl\Models;

class BlueprintMigration extends Model
{
    protected $table = 'blueprint_migrations';

    protected $fillable = [
        'migration_id', 'from_version', 'to_version',
        'status', 'started_at', 'completed_at', 'duration_ms',
        'rollbackable', 'error', 'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'rollbackable' => 'boolean',
        'meta' => 'array',
    ];
}