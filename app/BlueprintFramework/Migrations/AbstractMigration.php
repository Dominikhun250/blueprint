<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

abstract class AbstractMigration implements BlueprintMigrationInterface
{
    public function isRollbackable(): bool
    {
        return false;
    }

    public function appliesTo(string $installedVersion): bool
    {
        if ($this->fromVersion() === null) {
            return true;
        }
        return $this->fromVersion() === $installedVersion;
    }

    public function down(MigrationContext $context): void
    {
        throw new \RuntimeException(
            'Migration ' . $this->id() . ' does not implement rollback.'
        );
    }
}