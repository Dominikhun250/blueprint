<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

class MigrationResolver
{
    public function __construct(
        private Registry $registry,
        private MigrationRepository $repository,
    ) {
    }

    /**
     * @return BlueprintMigrationInterface[]
     */
    public function pending(string $currentVersion): array
    {
        $pending = [];
        foreach ($this->registry->all() as $migration) {
            if ($this->repository->hasRun($migration->id())) {
                continue;
            }
            if ($migration->appliesTo($currentVersion)) {
                $pending[] = $migration;
            }
        }
        return $pending;
    }

    public function find(string $id): ?BlueprintMigrationInterface
    {
        return $this->registry->find($id);
    }

    /** @return BlueprintMigrationInterface[] */
    public function all(): array
    {
        return $this->registry->all();
    }
}