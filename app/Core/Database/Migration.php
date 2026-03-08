<?php

namespace App\Core\Database;

use System\Database\Database;

/**
 * Abstract Migration Class
 * 
 * Provides a base for creating and dropping database tables.
 */
abstract class Migration
{
    /**
     * @var \PDO
     */
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Run the migrations.
     */
    abstract public function up();

    /**
     * Reverse the migrations.
     */
    abstract public function down();

    /**
     * Execute a raw SQL query.
     */
    protected function execute(string $sql)
    {
        return $this->db->exec($sql);
    }
}
