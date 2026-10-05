<?php

namespace Pterodactyl\BlueprintFramework\Doctor;

use Pterodactyl\BlueprintFramework\Doctor\Checks\BlueprintCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\CacheCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\ConfigurationCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\DatabaseCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\EnvironmentCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\ExtensionCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\IntegrityCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\LogCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\PermissionsCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\PterodactylCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\QueueCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\ResourceCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\RouteCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\SecurityCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\StorageCheck;
use Pterodactyl\BlueprintFramework\Doctor\Checks\VersionCheck;
use Illuminate\Support\Facades\Artisan;

class DoctorService
{
    /** @var CheckInterface[] */
    private array $checks;

    public function __construct(
        EnvironmentCheck $environment,
        BlueprintCheck $blueprint,
        PterodactylCheck $pterodactyl,
        ConfigurationCheck $configuration,
        DatabaseCheck $database,
        CacheCheck $cache,
        ExtensionCheck $extensions,
        IntegrityCheck $integrity,
        QueueCheck $queue,
        PermissionsCheck $permissions,
        StorageCheck $storage,
        LogCheck $logs,
        SecurityCheck $security,
        RouteCheck $routes,
        VersionCheck $version,
        ResourceCheck $resources,
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
            $queue,
            $permissions,
            $storage,
            $logs,
            $security,
            $routes,
            $version,
            $resources,
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

        if (str_contains($title, 'Configuration cache')) {
            try {
                Artisan::call('config:clear');
                Artisan::call('config:cache');
                return ['label' => 'Rebuilt configuration cache', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Rebuild configuration cache', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        if (str_contains($title, 'Blueprint cache')) {
            try {
                Artisan::call('bp:cache');
                return ['label' => 'Rebuilt Blueprint cache', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Rebuild Blueprint cache', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        if (str_contains($title, 'Pending database migrations')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                return ['label' => 'Applied pending database migrations', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Apply pending database migrations', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        if (str_contains($title, 'installation incomplete')) {
            $path = base_path('.blueprint/extensions/blueprint/private/db/is_installed');
            if (@touch($path)) {
                return ['label' => 'Recreated is_installed marker', 'ok' => true, 'detail' => null];
            }
            return ['label' => 'Recreate is_installed marker', 'ok' => false, 'detail' => 'Permission denied'];
        }

        if (str_contains($title, 'configuration not seeded')) {
            try {
                Artisan::call('db:seed', ['--class' => 'BlueprintSeeder', '--force' => true]);
                return ['label' => 'Seeded Blueprint configuration', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Seed Blueprint configuration', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        if (str_contains($title, 'public/storage link')) {
            try {
                Artisan::call('storage:link');
                return ['label' => 'Created public/storage link', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Create public/storage link', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        if (str_contains($title, 'Admin extensions route not registered')) {
            try {
                Artisan::call('route:clear');
                return ['label' => 'Cleared route cache', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Clear route cache', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        if (str_contains($title, 'Latest version unknown')) {
            try {
                Artisan::call('bp:version:cache');
                return ['label' => 'Cached latest Blueprint version', 'ok' => true, 'detail' => null];
            } catch (\Throwable $e) {
                return ['label' => 'Cache latest Blueprint version', 'ok' => false, 'detail' => $e->getMessage()];
            }
        }

        return null;
    }
}