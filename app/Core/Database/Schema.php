<?php

namespace App\Core\Database;

use System\Database\Database;

class Schema
{
    /**
     * Create a new table
     */
    public static function create(string $table, \Closure $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $columnsDefinition = $blueprint->build();
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` ({$columnsDefinition});";
        
        static::execute($sql);
    }

    /**
     * Drop a table if it exists
     */
    public static function dropIfExists(string $table)
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`;";
        static::execute($sql);
    }

    /**
     * Drop a table
     */
    public static function drop(string $table)
    {
        $sql = "DROP TABLE `{$table}`;";
        static::execute($sql);
    }

    /**
     * Check if a table exists
     */
    public static function hasTable(string $table): bool
    {
        $db = Database::getInstance()->getConnection();
        
        // Handling both SQLite and MySQL table existence checks
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:table");
        } else {
            $stmt = $db->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table");
        }
        
        $stmt->execute(['table' => $table]);
        return (bool) $stmt->fetch();
    }

    /**
     * Execute the raw SQL against the connection
     */
    protected static function execute(string $sql)
    {
        $db = Database::getInstance()->getConnection();
        return $db->exec($sql);
    }
}
