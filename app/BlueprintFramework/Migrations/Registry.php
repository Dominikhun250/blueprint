<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

class Registry
{
    /** @return string[] */
    public function classes(): array
    {
        return [
            // Definitions\ExampleNoopMigration::class,
        ];
    }

    /** @return BlueprintMigrationInterface[] */
    public function all(): array
    {
        $instances = [];
        foreach ($this->classes() as $class) {
            $instance = app()->make($class);
            if ($instance instanceof BlueprintMigrationInterface) {
                $instances[$instance->id()] = $instance;
            }
        }
        ksort($instances);
        return array_values($instances);
    }

    public function find(string $id): ?BlueprintMigrationInterface
    {
        foreach ($this->all() as $m) {
            if ($m->id() === $id) {
                return $m;
            }
        }
        return null;
    }
}