<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Console\BlueprintConsoleLibrary as BlueprintLibrary;

class MigrationContext
{
    public function __construct(
        public readonly string $fromVersion,
        public readonly string $toVersion,
        public readonly BlueprintLibrary $blueprint,
    ) {
    }

    public function path(string $relative = ''): string
    {
        return base_path($relative);
    }
}