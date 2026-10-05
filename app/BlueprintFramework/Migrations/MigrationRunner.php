<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

use Illuminate\Support\Facades\Log;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Console\BlueprintConsoleLibrary as BlueprintLibrary;

class MigrationRunner
{
    public function __construct(
        private MigrationRepository $repository,
        private BlueprintLibrary $blueprint,
    ) {
    }

    public function run(
        BlueprintMigrationInterface $migration,
        string $fromVersion,
        string $toVersion,
        ?callable $logger = null,
    ): bool {
        $id = $migration->id();
        $started = now();
        $start = microtime(true);

        $this->repository->record(new MigrationRecord(
            id: $id,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
            status: 'running',
            startedAt: $started->toDateTimeString(),
            rollbackable: $migration->isRollbackable(),
        ));

        $context = new MigrationContext(
            fromVersion: $fromVersion,
            toVersion: $toVersion,
            blueprint: $this->blueprint,
        );

        try {
            $migration->up($context);
        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $this->repository->record(new MigrationRecord(
                id: $id,
                fromVersion: $fromVersion,
                toVersion: $toVersion,
                status: 'failed',
                startedAt: $started->toDateTimeString(),
                completedAt: now()->toDateTimeString(),
                durationMs: $duration,
                rollbackable: $migration->isRollbackable(),
                error: $e->getMessage(),
            ));

            Log::error("Blueprint migration {$id} failed: {$e->getMessage()}");
            if ($logger) {
                $logger("Migration {$id} failed: {$e->getMessage()}");
            }
            return false;
        }

        $duration = (int) ((microtime(true) - $start) * 1000);
        $this->repository->record(new MigrationRecord(
            id: $id,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
            status: 'success',
            startedAt: $started->toDateTimeString(),
            completedAt: now()->toDateTimeString(),
            durationMs: $duration,
            rollbackable: $migration->isRollbackable(),
        ));

        Log::info("Blueprint migration {$id} succeeded ({$duration}ms)");
        if ($logger) {
            $logger("Migration {$id} succeeded");
        }
        return true;
    }

    public function rollback(BlueprintMigrationInterface $migration): bool
    {
        if (!$migration->isRollbackable()) {
            return false;
        }

        $context = new MigrationContext(
            fromVersion: $migration->toVersion() ?? '',
            toVersion: $migration->fromVersion() ?? '',
            blueprint: $this->blueprint,
        );

        try {
            $migration->down($context);
        } catch (\Throwable $e) {
            Log::error("Rollback of {$migration->id()} failed: {$e->getMessage()}");
            return false;
        }

        $record = $this->repository->find($migration->id());
        if ($record) {
            $record->status = 'rolled_back';
            $record->save();
        }

        return true;
    }
}