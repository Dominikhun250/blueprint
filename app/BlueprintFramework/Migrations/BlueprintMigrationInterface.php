<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

interface BlueprintMigrationInterface
{
    public function id(): string;

    public function description(): string;

    public function fromVersion(): ?string;

    public function toVersion(): ?string;

    public function isRollbackable(): bool;

    public function appliesTo(string $installedVersion): bool;

    /**
     * @param MigrationContext $context
     */
    public function up(MigrationContext $context): void;

    public function down(MigrationContext $context): void;
}