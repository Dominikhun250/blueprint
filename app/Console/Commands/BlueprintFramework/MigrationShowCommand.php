<?php

namespace Pterodactyl\Console\Commands\BlueprintFramework;

use Illuminate\Console\Command;
use Pterodactyl\BlueprintFramework\Migrations\MigrationRepository;

class MigrationShowCommand extends Command
{
    protected $description = 'Show details about a specific Blueprint migration';
    protected $signature = 'bp:migration:show {id}';

    public function __construct(private MigrationRepository $repository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $id = $this->argument('id');
        $record = $this->repository->find($id);

        if (!$record) {
            $this->error("Migration '{$id}' not found.");
            return 1;
        }

        $this->line("ID:           {$record->migration_id}");
        $this->line("From:         {$record->from_version}");
        $this->line("To:           {$record->to_version}");
        $this->line("Status:       {$record->status}");
        $this->line("Started at:   " . optional($record->started_at)->toDateTimeString());
        $this->line("Completed at: " . optional($record->completed_at)->toDateTimeString());
        $this->line("Duration:     " . ($record->duration_ms !== null ? $record->duration_ms . 'ms' : '—'));
        $this->line("Rollbackable: " . ($record->rollbackable ? 'yes' : 'no'));
        if ($record->error) {
            $this->newLine();
            $this->line("Error:        {$record->error}");
        }

        return 0;
    }
}