<?php

namespace App\Core\Database;

use System\Database\Database;

class MigrationRunner
{
    protected $db;
    protected $migrationsPath;

    public function __construct(string $migrationsPath)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->migrationsPath = rtrim($migrationsPath, '/');
    }

    /**
     * Run pending migrations
     */
    public function migrate()
    {
        $this->ensureMigrationsTableExists();

        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();

        $migrated = false;

        foreach ($files as $file) {
            $baseName = basename($file);
            if (!in_array($baseName, $executed)) {
                $this->runMigration($file, 'up');
                
                $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                // We simplify batch tracking to just 1 for now, or get max batch + 1
                $batch = $this->getNextBatchNumber();
                $stmt->execute([$baseName, $batch]);
                
                echo "\033[32mMigrated: {$baseName}\033[0m\n";
                $migrated = true;
            }
        }

        if (!$migrated) {
            echo "Nothing to migrate.\n";
        }
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback()
    {
        $this->ensureMigrationsTableExists();

        $batch = $this->getLastBatchNumber();
        if ($batch === 0) {
            echo "Nothing to rollback.\n";
            return;
        }

        $stmt = $this->db->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$batch]);
        $migrationsToRollback = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($migrationsToRollback as $migrationName) {
            $file = $this->migrationsPath . '/' . $migrationName;
            if (file_exists($file)) {
                $this->runMigration($file, 'down');
                
                $delStmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
                $delStmt->execute([$migrationName]);
                
                echo "\033[33mRolled back: {$migrationName}\033[0m\n";
            }
        }
    }

    /**
     * Rollback all and migrate again
     */
    public function refresh()
    {
        echo "Rolling back all migrations...\n";
        while ($this->getLastBatchNumber() > 0) {
            $this->rollback();
        }
        echo "Running migrations...\n";
        $this->migrate();
    }

    protected function runMigration($file, $method)
    {
        require_once $file;
        $baseName = basename($file);
        
        $className = $this->getClassNameFromName($baseName);
        
        if (class_exists($className)) {
            $migration = new $className();
            if (method_exists($migration, $method)) {
                $migration->$method();
            }
        }
    }

    protected function getClassNameFromName($fileName)
    {
        $nameWithoutExt = str_replace('.php', '', $fileName);
        // Format: 2023_10_05_123456_create_users_table
        $parts = explode('_', $nameWithoutExt);
        
        // Find the index where the actual name starts (usually after 4 datetime parts: Y, m, d, His)
        // If the format is YYYY-MM-DD-His_name, then it's at index 1. Let's look for non-numeric parts
        $className = '';
        foreach ($parts as $part) {
            if (!is_numeric($part)) {
                $className .= ucfirst($part);
            }
        }
        return $className;
    }

    protected function ensureMigrationsTableExists()
    {
        if (!Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
                $table->timestamps();
            });
        }
    }

    protected function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    protected function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    protected function getLastBatchNumber(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) FROM migrations");
        $max = $stmt->fetchColumn();
        return $max ? (int) $max : 0;
    }

    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        sort($files);
        return $files;
    }
}
