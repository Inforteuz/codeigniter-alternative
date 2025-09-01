<?php

/**
 * BaseModel.php
 *
 * This file contains the base model class for your custom MVC framework.
 * It's meant to be extended by other models and includes shared functionality.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    1.1.0
 * @date       2024-12-02
 *
 * Description:
 * This class covers a few key things:
 *
 * 1. **Database connection**:
 *    - Sets up the database connection in the constructor.
 *    - The $db object can be used to run queries easily.
 *
 * 2. **Running queries**:
 *    - `query($sql, $params)` – Runs raw SQL with optional parameters and returns the result.
 *
 * 3. **Logging errors**:
 *    - `logError($message)` – Logs DB-related or general errors to a log file.
 *
 * 4. **Creating tables**:
 *    - `createTableIfNotExists($tableName, $schema)` – Creates a DB table if it doesn’t already exist.
 *
 * @class BaseModel
 *
 * @methods
 * - `__construct()`: Initializes the model and sets up DB connection.
 * - `query($sql, $params)`: Executes an SQL query and returns the result.
 * - `logError($message)`: Logs errors for debugging or system monitoring.
 * - `createTableIfNotExists($tableName, $schema)`: Creates a new table if it’s not in the DB.
 *
 * @properties
 * - `$db`: Holds the database object for running queries.
 *
 * @example
 * ```php
 * class UserModel extends BaseModel
 * {
 *     public function getAllUsers()
 *     {
 *         $sql = "SELECT * FROM users";
 *         return $this->query($sql);
 *     }
 * }
 * ```
 */


namespace System;

use System\Database\Database;

class BaseModel
{
    protected $db;
    protected $table;
    protected $lastWhere = '';
    protected $lastParams = [];
    protected $lastOrder = '';
    protected $lastLimit = '';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log errors into the log file.
     *
     * @param string $message
     * @param string|null $sql
     */
    protected function logError($message, $sql = null)
    {
        $logDir = __DIR__ . "/../writable/logs";
        date_default_timezone_set("Asia/Tashkent");

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . "/error_" . date("Y-m-d") . ".log";
        $dateTime = date("Y-m-d H:i:s");
        $logMessage = "[{$dateTime}] ERROR: {$message}";

        if ($sql) {
            $logMessage .= " | SQL: {$sql}";
        }

        $logMessage .= "\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Prepare an SQL statement
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function prepare($sql, $params = [])
    {
        try {
            return $this->db->getConnection()->prepare($sql);
        } catch (\PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage());
            throw new \Exception("So'rov bajarishda xatolik yuz berdi.");
        }
    }

    /**
     * Insert data into a table.
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insert($table, $data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->prepare($sql);

        try {
            return $stmt->execute($data);
        } catch (\PDOException $e) {
            $this->logError("Insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find a single record by ID.
     *
     * @param string $table
     * @param int $id
     * @return array|null
     */
    public function findById($table, $id)
    {
        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $params = ['id' => $id];
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Update data in a table with conditions.
     *
     * @param string $table
     * @param array $data
     * @param array $conditions
     * @return bool
     */
    public function update($table, $data, $conditions)
    {
        $setClause = implode(", ", array_map(fn($key) => "{$key} = :{$key}", array_keys($data)));
        $whereClause = implode(" AND ", array_map(fn($key) => "{$key} = :cond_{$key}", array_keys($conditions)));

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";

        $params = array_merge($data, array_combine(
            array_map(fn($key) => "cond_{$key}", array_keys($conditions)),
            array_values($conditions)
        ));

        $stmt = $this->prepare($sql);

        try {
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            $this->logError("Update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete records based on conditions.
     *
     * @param string $table
     * @param array $conditions
     * @return bool
     */
    public function delete($table, $conditions)
    {
        $whereClause = implode(" AND ", array_map(fn($key) => "{$key} = :{$key}", array_keys($conditions)));
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";

        $stmt = $this->prepare($sql);

        try {
            return $stmt->execute($conditions);
        } catch (\PDOException $e) {
            $this->logError("Delete failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a record exists based on conditions.
     *
     * @param string $table
     * @param array $conditions
     * @return bool
     */
    public function exists($table, $conditions)
    {
        $whereClause = implode(" AND ", array_map(fn($key) => "{$key} = :{$key}", array_keys($conditions)));
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$whereClause}";

        $stmt = $this->prepare($sql);

        try {
            $stmt->execute($conditions);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (\PDOException $e) {
            $this->logError("Exists check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count records in a table with optional conditions.
     *
     * @param string $table
     * @param array $conditions
     * @return int
     */
    public function count($table, $conditions = [])
    {
        $whereClause = $conditions
            ? "WHERE " . implode(" AND ", array_map(fn($key) => "{$key} = :{$key}", array_keys($conditions)))
            : "";

        $sql = "SELECT COUNT(*) as count FROM {$table} {$whereClause}";

        $stmt = $this->prepare($sql);

        try {
            $stmt->execute($conditions);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (\PDOException $e) {
            $this->logError("Count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Paginate records from a table.
     *
     * @param string $table
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function paginate($table, $limit, $offset)
    {
        $sql = "SELECT * FROM {$table} LIMIT :limit OFFSET :offset";

        $stmt = $this->prepare($sql);

        try {
            $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logError("Pagination failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Execute the query and return the results.
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function query($sql, $params = [])
    {
        date_default_timezone_set("Asia/Tashkent");
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage(), $sql);
            throw new \Exception("So'rov bajarishda xatolik yuz berdi.");
        }
    }

    /**
     * Create table if not exists.
     *
     * @param string $tableName
     * @param string $schema
     */
    protected function createTableIfNotExists($tableName, $schema)
    {
        try {
            $createTableSQL = "CREATE TABLE IF NOT EXISTS {$tableName} ({$schema}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->query($createTableSQL);
        } catch (\Exception $e) {
            $this->logError("Jadvalni yaratishda xatolik: {$e->getMessage()}");
        }
    }

    /**
     * Where condition for query builder
     *
     * @param array $conditions
     * @return self
     */
    public function where($conditions = [])
    {
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $field => $value) {
                $whereParts[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $whereClause = "WHERE " . implode(" AND ", $whereParts);
        }
        
        $this->lastWhere = $whereClause;
        $this->lastParams = $params;
        
        return $this;
    }

    /**
     * Order by for query builder
     *
     * @param string $field
     * @param string $direction
     * @return self
     */
    public function orderBy($field, $direction = 'ASC')
    {
        $this->lastOrder = "ORDER BY {$field} {$direction}";
        return $this;
    }

    /**
     * Limit for query builder
     *
     * @param int $limit
     * @param int $offset
     * @return self
     */
    public function limit($limit, $offset = 0)
    {
        $this->lastLimit = "LIMIT {$limit} OFFSET {$offset}";
        return $this;
    }

    /**
     * Get first record
     *
     * @return array|null
     */
    public function first()
    {
        $sql = "SELECT * FROM {$this->table} {$this->lastWhere} {$this->lastOrder} LIMIT 1";
        $result = $this->query($sql, $this->lastParams);
        return $result[0] ?? null;
    }

    /**
     * Get all records
     *
     * @return array
     */
    public function get()
    {
        $sql = "SELECT * FROM {$this->table} {$this->lastWhere} {$this->lastOrder} {$this->lastLimit}";
        return $this->query($sql, $this->lastParams);
    }

    /**
     * Get all records from table
     *
     * @param string $table
     * @return array
     */
    public function all($table = null)
    {
        $table = $table ?: $this->table;
        $sql = "SELECT * FROM {$table}";
        return $this->query($sql);
    }
    
    /**
     * createUser
     *
     * This function inserts the default super admin user into the "users" table.
     * User information is predetermined and the password is stored as hashed.
     */
    public function createUser()
    {
        $data = [
            "id" => 1,
            "user_id" => "USER-67485ced924fb",
            // ...
        ];

        $this->insert("users", $data);
    } 
}

?>