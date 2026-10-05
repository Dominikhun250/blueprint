<?php

namespace Pterodactyl\Console\Commands\BlueprintFramework;

use Illuminate\Console\Command;
use Pterodactyl\BlueprintFramework\Migrations\MigrationManager;

class MigrationRollbackCommand extends Command
{
    protected $description = 'Roll back the most recent (or a specific) Blueprint migration';
    protected $signature = 'bp:migration:rollback {id? : Migration ID to roll back}';

    public function __construct(private MigrationManager $manager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $id = $this->argument('id');

        if ($id !== null) {
            $migration = $this->manager->find($id);
            if (!$migration) {
                $this->error("Migration '{$id}' not found.");
                return 1;
            }
            if (!$migration->isRollbackable()) {
                $this->error('Migration cannot be automatically rolled back.');
                $this->newLine();
                $this->line('Reason:');
                $this->line('  This migration contains an irreversible data transformation.');
                $this->newLine();
                $this->line('Manual recovery information:');
                $this->line('  Restore from a Blueprint snapshot, or reinstall Blueprint at the desired version.');
                return 1;
            }
        }

        if (!$this->confirm('Proceed with rollback?', false)) {
            $this->line('Aborted.');
            return 1;
        }

        $result = $this->manager->rollback($id);

        if (!$result['ok']) {
            $this->error($result['error'] ?? 'Rollback failed.');
            return 1;
        }

        $this->info("Rolled back migration: {$result['id']}");
        return 0;
    }
}