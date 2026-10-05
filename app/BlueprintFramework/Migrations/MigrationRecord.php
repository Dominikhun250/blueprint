<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

class MigrationRecord
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $fromVersion,
        public readonly ?string $toVersion,
        public readonly string $status,
        public readonly ?string $startedAt = null,
        public readonly ?string $completedAt = null,
        public readonly ?int $durationMs = null,
        public readonly bool $rollbackable = false,
        public readonly ?string $error = null,
        public readonly array $meta = [],
    ) {
    }
}