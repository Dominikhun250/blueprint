<?php

namespace Pterodactyl\Console\Commands\BlueprintFramework;

use Illuminate\Console\Command;
use Pterodactyl\BlueprintFramework\Migrations\MigrationManager;
use Pterodactyl\BlueprintFramework\Services\PlaceholderService\BlueprintPlaceholderService;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Console\BlueprintConsoleLibrary as BlueprintLibrary;

class MigrationStatusCommand extends Command
{
    protected $description = 'Show Blueprint migration status';
    protected $signature = 'bp:migration:status';

    public function __construct(
        private MigrationManager $manager,
        private BlueprintPlaceholderService $placeholder,
        private BlueprintLibrary $blueprint,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $current = $this->placeholder->version();
        $this->line("Current version: {$current}");
        $this->newLine();

        $pending = $this->manager->describePending($current);
        if (empty($pending)) {
            $this->line('Pending:');
            $this->line('  <fg=gray>none</>');
        } else {
            $this->line('Pending:');
            foreach ($pending as $p) {
                $this->line("  {$p['id']}  ({$p['description']})");
            }
        }
        $this->newLine();

        $failed = $this->manager->failed();
        if (!empty($failed)) {
            $this->line('Failed:');
            foreach ($failed as $f) {
                $this->line("  {$f->migration_id}  {$f->error}");
            }
            $this->newLine();
        }

        $this->line('Extensions:');
        foreach (array_filter($this->blueprint->extensions()) as $id) {
            $conf = $this->blueprint->extensionConfig($id);
            $target = $conf['info']['target'] ?? '';
            if ($target === '' || $current === 'rolling' || $target === $current) {
                $this->line("  <fg=green>✓</> {$id}");
            } else {
                $this->line("  <fg=yellow>⚠</> {$id} (targets {$target})");
            }
        }
        $this->newLine();

        $status = 'READY';
        if (!empty($failed)) {
            $status = 'FAILED';
        } elseif (!empty($pending)) {
            $status = 'PENDING';
        }
        $this->line("Status: {$status}");

        return 0;
    }
}