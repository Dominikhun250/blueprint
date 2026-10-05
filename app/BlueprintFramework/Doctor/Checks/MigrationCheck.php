<?php

namespace Pterodactyl\BlueprintFramework\Doctor\Checks;

use Pterodactyl\BlueprintFramework\Doctor\CheckInterface;
use Pterodactyl\BlueprintFramework\Doctor\CheckResult;
use Pterodactyl\BlueprintFramework\Doctor\Issue;
use Pterodactyl\BlueprintFramework\Doctor\Severity;
use Pterodactyl\BlueprintFramework\Migrations\MigrationManager;
use Pterodactyl\BlueprintFramework\Services\PlaceholderService\BlueprintPlaceholderService;

class MigrationCheck implements CheckInterface
{
    public function __construct(
        private MigrationManager $manager,
        private BlueprintPlaceholderService $placeholder,
    ) {
    }

    public function name(): string { return 'migrations'; }
    public function title(): string { return 'Blueprint Migrations'; }

    public function run(): CheckResult
    {
        $r = new CheckResult($this->name(), $this->title());

        try {
            $current = $this->placeholder->version();
            $pending = $this->manager->pending($current);

            if (empty($pending)) {
                $r->ok('State', 'Up to date');
            } else {
                $r->warn('State', count($pending) . ' pending');
                $r->issue(new Issue(
                    Severity::WARNING,
                    'Pending Blueprint migrations',
                    'Migrations: ' . implode(', ', array_map(
                        fn ($m) => $m->id(),
                        $pending
                    )),
                    'Run: blueprint migrate',
                    true,
                ));
            }

            $failed = $this->manager->failed();
            if (!empty($failed)) {
                $r->err('Failed', count($failed) . ' failed');
                $r->issue(new Issue(
                    Severity::ERROR,
                    'Failed Blueprint migrations detected',
                    'Run `blueprint migration history` for details.',
                    'Resolve the failure and rerun `blueprint migrate`.',
                ));
            }
        } catch (\Throwable $e) {
            $r->warn('State', 'Unavailable');
            $r->issue(new Issue(
                Severity::WARNING,
                'Migration state unavailable',
                $e->getMessage(),
            ));
        }

        return $r;
    }
}