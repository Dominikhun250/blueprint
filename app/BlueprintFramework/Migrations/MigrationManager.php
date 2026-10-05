<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

use Pterodactyl\BlueprintFramework\Services\PlaceholderService\BlueprintPlaceholderService;

class MigrationManager
{
    public function __construct(
        private MigrationResolver $resolver,
        private MigrationRunner $runner,
        private MigrationRepository $repository,
        private MigrationLock $lock,
        private BlueprintPlaceholderService $placeholder,
    ) {
    }

    /** @return BlueprintMigrationInterface[] */
    public function pending(string $currentVersion): array
    {
        return $this->resolver->pending($currentVersion);
    }

    /** @return array<int,array{id:string,from:?string,to:?string,description:string,rollbackable:bool}> */
    public function describePending(string $currentVersion): array
    {
        return array_map(fn (BlueprintMigrationInterface $m) => [
            'id' => $m->id(),
            'from' => $m->fromVersion(),
            'to' => $m->toVersion(),
            'description' => $m->description(),
            'rollbackable' => $m->isRollbackable(),
        ], $this->pending($currentVersion));
    }

    public function failed(): array
    {
        return $this->repository->failed();
    }

    public function history(): array
    {
        return $this->repository->all();
    }

    public function find(string $id): ?BlueprintMigrationInterface
    {
        return $this->resolver->find($id);
    }

    /**
     * @return array{ok:bool,ran:string[],failed:?string,error:?string}
     */
    public function migrate(?string $fromVersion = null, ?string $targetVersion = null, ?callable $logger = null): array
    {
        if ($this->lock->isHeld()) {
            return ['ok' => false, 'ran' => [], 'failed' => null, 'error' => 'A migration is already running.'];
        }
        if (!$this->lock->acquire()) {
            return ['ok' => false, 'ran' => [], 'failed' => null, 'error' => 'Could not acquire migration lock.'];
        }

        $ran = [];
        try {
            $current = $fromVersion ?? $this->placeholder->version();
            $pending = $this->pending($current);

            foreach ($pending as $migration) {
                $target = $targetVersion ?? $migration->toVersion() ?? $current;
                $ok = $this->runner->run($migration, $current, $target, $logger);
                if (!$ok) {
                    return ['ok' => false, 'ran' => $ran, 'failed' => $migration->id(), 'error' => 'Migration failed.'];
                }
                $ran[] = $migration->id();
                $current = $target;
            }
        } finally {
            $this->lock->release();
        }

        return ['ok' => true, 'ran' => $ran, 'failed' => null, 'error' => null];
    }

    /**
     * @return array{ok:bool,id:?string,error:?string}
     */
    public function rollback(?string $id = null): array
    {
        if ($this->lock->isHeld()) {
            return ['ok' => false, 'id' => null, 'error' => 'A migration is already running.'];
        }
        if (!$this->lock->acquire()) {
            return ['ok' => false, 'id' => null, 'error' => 'Could not acquire migration lock.'];
        }

        try {
            $migration = null;
            if ($id !== null) {
                $migration = $this->resolver->find($id);
                if (!$migration) {
                    return ['ok' => false, 'id' => $id, 'error' => "Migration '{$id}' not found."];
                }
                if (!$migration->isRollbackable()) {
                    return ['ok' => false, 'id' => $id, 'error' => 'Migration is not rollbackable.'];
                }
            } else {
                // Most recent successful rollbackable migration
                $history = array_reverse($this->repository->all());
                foreach ($history as $record) {
                    if ($record->status === 'success' && $record->rollbackable) {
                        $migration = $this->resolver->find($record->migration_id);
                        if ($migration) {
                            $id = $migration->id();
                            break;
                        }
                    }
                }
                if (!$migration) {
                    return ['ok' => false, 'id' => null, 'error' => 'No rollbackable migration found.'];
                }
            }

            $ok = $this->runner->rollback($migration);
            return ['ok' => $ok, 'id' => $migration->id(), 'error' => $ok ? null : 'Rollback failed.'];
        } finally {
            $this->lock->release();
        }
    }
}