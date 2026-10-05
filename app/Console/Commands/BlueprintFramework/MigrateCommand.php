<?php

namespace Pterodactyl\Console\Commands\BlueprintFramework;

use Illuminate\Console\Command;
use Pterodactyl\BlueprintFramework\Migrations\MigrationManager;
use Pterodactyl\BlueprintFramework\Services\PlaceholderService\BlueprintPlaceholderService;

class MigrateCommand extends Command
{
    protected $description = 'Run pending Blueprint migrations';
    protected $signature = 'bp:migrate
        {--force : Run without confirmation}
        {--from= : Override the source version}
        {--to= : Override the target version}';

    public function __construct(
        private MigrationManager $manager,
        private BlueprintPlaceholderService $placeholder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $from = $this->option('from') ?: $this->placeholder->version();
        $to = $this->option('to') ?: null;

        $pending = $this->manager->describePending($from);

        if (empty($pending)) {
            $this->info('No pending Blueprint migrations.');
            return 0;
        }

        $this->line('Pending Blueprint migrations:');
        foreach ($pending as $p) {
            $this->line(sprintf('  %-40s %s', $p['id'], $p['description']));
        }
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Apply these migrations?', true)) {
            $this->warn('Aborted.');
            return 1;
        }

        $result = $this->manager->migrate(
            fromVersion: $from,
            targetVersion: $to,
            logger: fn (string $m) => $this->line("  {$m}"),
        );

        if (!$result['ok']) {
            $this->error($result['error'] ?? 'Migration failed.');
            if ($result['failed']) {
                $this->error('Failed migration: ' . $result['failed']);
            }
            return 1;
        }

        $this->info('Applied ' . count($result['ran']) . ' migration(s).');
        return 0;
    }
}