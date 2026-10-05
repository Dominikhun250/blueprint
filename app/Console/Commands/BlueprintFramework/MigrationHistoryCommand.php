<?php

namespace Pterodactyl\Console\Commands\BlueprintFramework;

use Illuminate\Console\Command;
use Pterodactyl\BlueprintFramework\Migrations\MigrationManager;

class MigrationHistoryCommand extends Command
{
    protected $description = 'Show Blueprint migration history';
    protected $signature = 'bp:migration:history';

    public function __construct(private MigrationManager $manager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $rows = $this->manager->history();

        if (empty($rows)) {
            $this->line('No migrations have been run.');
            return 0;
        }

        $this->table(
            ['ID', 'From', 'To', 'Status', 'Run at', 'Duration'],
            array_map(fn ($r) => [
                $r->id,
                $r->from_version ?? '—',
                $r->to_version ?? '—',
                strtoupper($r->status),
                optional($r->completed_at)->toDateTimeString() ?? '—',
                $r->duration_ms !== null ? $r->duration_ms . 'ms' : '—',
            ], $rows)
        );

        return 0;
    }
}