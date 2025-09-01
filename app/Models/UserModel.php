<?php

namespace App\Models;

use System\Database\Database;

use PDO;

/**
 * Class UserModel
 * 
 * Handles user-related database operations.
 */
class UserModel
{
    private $db; // Database connection instance

    /**
     * Constructor initializes the Database connection.
     */
    public function __construct()
    {
        $this->db = new Database();
    }
}
?>