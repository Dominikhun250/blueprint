<?php

namespace Pterodactyl\BlueprintFramework\Doctor;

use Pterodactyl\BlueprintFramework\Doctor\Checks\BlueprintCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\CacheCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\ConfigurationCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\DatabaseCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\EnvironmentCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\ExtensionCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\IntegrityCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\PterodactylCheck;
use Illuminate\Support\Facades\Artisan;

class DoctorService
{
    /** @var CheckInterface[] */
    private array $checks;

    public function __construct(
        EnvironmentCheck $environment,
        BlueprintCheck $blueprint,
        PterodactylCheck $pterodactyl,
        DatabaseCheck $database,
        ConfigurationCheck $configuration,
        CacheCheck $cache,
        ExtensionCheck $extensions,
        IntegrityCheck $integrity,
    ) {
        $this->checks = [
            $environment,
            $blueprint,
            $pterodactyl,
            $configuration,
            $database,
            $cache,
            $extensions,
            $integrity,
        ];
    }

    public function run(): DoctorReport
    {
        $report = new DoctorReport();
        foreach ($this->checks as $check) {
            try {
                $report->add($check->run());
            } catch (\Throwable $e) {
                $result = new CheckResult($check->name(), $check->title());
                $result->err('Check failed', $e->getMessage());
                $result->issue(new Issue(
                    Severity::ERROR,
                    "Check '{$check->name()}' crashed",
                    $e->getMessage(),
                ));
                $report->add($result);
            }
        }
        return $report;
    }

    /**
     * Apply safe, deterministic fixes only.
     *
     * @return array<int,array{label:string,ok:bool,detail:?string}>
     */
    public function fix(DoctorReport $report): array
    {
        $actions = [];

        foreach ($report->autoFixableIssues() as $issue) {
            $action = $this->applyFix($issue);
            if ($action !== null) {
                $actions[] = $action;
            } else {
                $actions[] = [
                    'label' => $issue->title,
                    'ok' => false,
                    'detail' => 'Manual intervention required.',
                ];
            }
        }

        return $actions;
    }

    private function applyFix(Issue $issue): ?array
    {
        $title = $issue->title;

        // Stale config cache
        if (str_contains($title, 'Configuration cache')) {
            try {
                Artisan::call('config:clear');
                Artisan::call('config:cache');
                return ['label' => 'Rebuilt configuration cache', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Rebuild configuration cache', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        // Blueprint cache
        if (str_contains($title, 'Blueprint cache')) {
            try {
                Artisan::call('bp:cache');
                return ['label' => 'Rebuilt Blueprint cache', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Rebuild Blueprint cache', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        // Pending Laravel migrations
        if (str_contains($title, 'Pending database migrations')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                return ['label' => 'Applied pending database migrations', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Apply pending database migrations', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        // Pending Blueprint migrations
        if (str_contains($title, 'Pending Blueprint migrations')) {
            try {
                Artisan::call('bp:migrate', ['--force' => true]);
                return ['label' => 'Applied pending Blueprint migrations', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Apply pending Blueprint migrations', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        // Install marker
        if (str_contains($title, 'installation incomplete')) {
            $path = base_path('.blueprint/extensions/blueprint/private/db/is_installed');
            if (@touch($path)) {
                return ['label' => 'Recreated is_installed marker', 'ok' => true, 'detail' => null];
            }
            return ['label' => 'Recreate is_installed marker', 'ok' => false, 'detail' => 'Permission denied'];
        }

        // Blueprint seed
        if (str_contains($title, 'configuration not seeded')) {
            try {
                Artisan::call('db:seed', ['--class' => 'BlueprintSeeder', '--force' => true]);
                return ['label' => 'Seeded Blueprint configuration', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Seed Blueprint configuration', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        return null;
    }
}