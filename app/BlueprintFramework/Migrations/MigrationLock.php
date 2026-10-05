<?php

namespace Pterodactyl\BlueprintFramework\Migrations;

class MigrationLock
{
    private string $lockPath;

    public function __construct()
    {
        $this->lockPath = base_path('.blueprint/migration.lock');
    }

    public function acquire(): bool
    {
        $dir = dirname($this->lockPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (file_exists($this->lockPath)) {
            // Detect stale lock (> 4 hours old)
            $age = time() - (int) @filemtime($this->lockPath);
            if ($age > 14400) {
                @unlink($this->lockPath);
            } else {
                return false;
            }
        }

        $handle = @fopen($this->lockPath, 'x');
        if ($handle === false) {
            return false;
        }
        fwrite($handle, (string) getmypid());
        fclose($handle);
        return true;
    }

    public function release(): void
    {
        @unlink($this->lockPath);
    }

    public function isHeld(): bool
    {
        return file_exists($this->lockPath);
    }
}