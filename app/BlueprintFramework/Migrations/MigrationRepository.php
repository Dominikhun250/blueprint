<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

use Pterodactyl\Models\BlueprintMigration;

class MigrationRepository
{
    public function record(MigrationRecord $record): void
    {
        BlueprintMigration::updateOrCreate(
            ['migration_id' => $record->id],
            [
                'from_version' => $record->fromVersion,
                'to_version' => $record->toVersion,
                'status' => $record->status,
                'started_at' => $record->startedAt,
                'completed_at' => $record->completedAt,
                'duration_ms' => $record->durationMs,
                'rollbackable' => $record->rollbackable,
                'error' => $record->error,
                'meta' => $record->meta,
            ]
        );
    }

    public function find(string $id): ?BlueprintMigration
    {
        return BlueprintMigration::where('migration_id', $id)->first();
    }

    /** @return BlueprintMigration[] */
    public function all(): array
    {
        return BlueprintMigration::orderBy('id')->get()->all();
    }

    public function hasRun(string $id): bool
    {
        return BlueprintMigration::where('migration_id', $id)
            ->where('status', 'success')
            ->exists();
    }

    public function hasFailed(string $id): bool
    {
        return BlueprintMigration::where('migration_id', $id)
            ->where('status', 'failed')
            ->exists();
    }

    /** @return BlueprintMigration[] */
    public function failed(): array
    {
        return BlueprintMigration::where('status', 'failed')->get()->all();
    }

    public function lastSuccessful(): ?BlueprintMigration
    {
        return BlueprintMigration::where('status', 'success')
            ->orderByDesc('id')
            ->first();
    }
}