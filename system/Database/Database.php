<?php

namespace System\Database;

use PDO;
use PDOException;
use System\Core\Env;
use System\Core\DebugToolbar;

/**
 * Database Class
 * 
 * Manages database connection and provides methods for CRUD operations using PDO.
 * Implements Singleton pattern to ensure a single connection instance.
 * 
 * Features:
 *  - Connects to a MySQL database using credentials from environment variables.
 *  - Supports prepared statements and parameter binding.
 *  - Provides common database operations like insert, update, delete, select.
 *  - Supports transactions and error logging.
 *  - Provides utility functions such as pagination, table existence check, and schema inspection.
 * 
 * @package System\Database
 * @version 1.0.0
 * @date 2024-12-01
 */
class Database
{
    /** @var Database|null Singleton instance */
    private static $instance = null;

    /** @var PDO|null PDO connection instance */
    private $connection;

    /** @var string Database host */
    private $host;

    /** @var string Database name */
    private $dbname;

    /** @var string Database username */
    private $username;

    /** @var string Database password */
    private $password;

    /** @var string Character set */
    private $charset;

    /**
     * Private constructor to prevent direct instantiation.
     * Loads environment variables and establishes a database connection.
     */
    private function __construct()
    {
        Env::load();

        $this->host = Env::get('DB_HOST', 'localhost');
        $this->dbname = Env::get('DB_NAME', 'performance_schema');
        $this->username = Env::get('DB_USER', 'root');
        $this->password = Env::get('DB_PASS', '');
        $this->charset = Env::get('DB_CHARSET', 'utf8mb4');

        $this->connect();
    }

    /**
     * Get the singleton instance of Database.
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the singleton instance.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the singleton instance.
     * 
     * @throws \Exception
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Establish the PDO connection to the database.
     * Sets timezone for the connection.
     * 
     * @throws \Exception if connection fails
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            $this->connection->exec("SET time_zone = '+05:00'");

        } catch (PDOException $e) {
            $this->logError("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection error occurred. Please contact the administrator.");
        }
    }

    /**
     * Log errors to a daily log file in writable/logs directory.
     * 
     * @param string $message Error message to log
     * @return void
     */
    public function logError($message)
    {
        $logDir = __DIR__ . '/../../writable/logs';
        date_default_timezone_set("Asia/Tashkent");

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        $dateTime = date('Y-m-d H:i:s');
        $logMessage = "[{$dateTime}] ERROR: {$message}\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Get the PDO connection object.
     * Connect if not already connected.
     * 
     * @return PDO
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Prepare a SQL statement.
     * 
     * @param string $sql SQL query
     * @param array $params Optional parameters for prepared statement
     * @return \PDOStatement
     * @throws \Exception on preparation failure
     */
    public function prepare($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError("Query preparation failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new \Exception("Failed to prepare SQL statement.");
        }
    }

    /**
     * Execute a SQL query with optional parameters.
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @return \PDOStatement
     * @throws \Exception on execution failure
     */
    public function execute($sql, $params = [])
    {
        $startTime = microtime(true);

        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);

            $executionTime = (microtime(true) - $startTime) * 1000; 

            DebugToolbar::addQuery($sql, $params, $executionTime);

            return $stmt;
        } catch (PDOException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            DebugToolbar::addQuery($sql, $params, $executionTime, false);

            $this->logError("Query execution failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new \Exception("Failed to execute SQL query.");
        }
    }

    /**
     * Fetch a single row from a query result.
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|false Associative array or false on failure
     */
    public function fetch($sql, $params = [])
    {
        try {
            $stmt = $this->execute($sql, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError("Fetch failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all rows from a query result.
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Array of rows or empty array on failure
     */
    public function fetchAll($sql, $params = [])
    {
        try {
            $stmt = $this->execute($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError("FetchAll failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insert a new row into a table.
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return string|false Last insert ID or false on failure
     */
    public function insert($table, $data)
    {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));

            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->prepare($sql);

            $stmt->execute($data);
            return $this->getConnection()->lastInsertId();

        } catch (PDOException $e) {
            $this->logError("Insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update rows in a table based on a condition.
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where SQL WHERE clause (without "WHERE")
     * @param array $whereParams Parameters for the WHERE clause
     * @return int|false Number of affected rows or false on failure
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        try {
            $setParts = [];
            foreach (array_keys($data) as $column) {
                $setParts[] = "{$column} = :{$column}";
            }

            $setClause = implode(', ', $setParts);
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

            $stmt = $this->prepare($sql);
            $stmt->execute(array_merge($data, $whereParams));

            return $stmt->rowCount();

        } catch (PDOException $e) {
            $this->logError("Update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete rows from a table based on a condition.
     * 
     * @param string $table Table name
     * @param string $where SQL WHERE clause (without "WHERE")
     * @param array $whereParams Parameters for the WHERE clause
     * @return int|false Number of affected rows or false on failure
     */
    public function delete($table, $where, $whereParams = [])
    {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->prepare($sql);
            $stmt->execute($whereParams);

            return $stmt->rowCount();

        } catch (PDOException $e) {
            $this->logError("Delete failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the last inserted ID from the database.
     * 
     * @return string
     */
    public function lastInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Begin a transaction.
     * 
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit the current transaction.
     * 
     * @return bool
     */
    public function commit()
    {
        return $this->getConnection()->commit();
    }

    /**
     * Roll back the current transaction.
     * 
     * @return bool
     */
    public function rollBack()
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Check if a table exists in the database.
     * 
     * @param string $tableName
     * @return bool
     */
    public function tableExists($tableName)
    {
        try {
            $sql = "SELECT 1 FROM information_schema.tables 
                    WHERE table_schema = ? AND table_name = ? LIMIT 1";
            $result = $this->fetch($sql, [$this->dbname, $tableName]);
            return !empty($result);
        } catch (PDOException $e) {
            $this->logError("Table exists check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new table with specified columns.
     * 
     * @param string $tableName Table name
     * @param string $columns Columns definition SQL fragment
     * @return bool Success status
     */
    public function createTable($tableName, $columns)
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$tableName} ({$columns}) 
                    ENGINE=InnoDB DEFAULT CHARSET={$this->charset}";
            $this->execute($sql);
            return true;
        } catch (PDOException $e) {
            $this->logError("Create table failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count the number of rows in a table with optional conditions.
     * 
     * @param string $table Table name
     * @param string $conditions Optional WHERE clause (without WHERE)
     * @param array $params Parameters for WHERE clause
     * @return int Number of rows
     */
    public function count($table, $conditions = "", $params = [])
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$table}";
            if (!empty($conditions)) {
                $sql .= " WHERE {$conditions}";
            }

            $result = $this->fetch($sql, $params);
            return $result ? (int)$result['count'] : 0;
        } catch (PDOException $e) {
            $this->logError("Count failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get an array of values for a specific column from a table.
     * 
     * @param string $table Table name
     * @param string $column Column name to pluck
     * @param string $conditions Optional WHERE clause (without WHERE)
     * @param array $params Parameters for WHERE clause
     * @return array Array of column values
     */
    public function pluck($table, $column, $conditions = "", $params = [])
    {
        try {
            $sql = "SELECT {$column} FROM {$table}";
            if (!empty($conditions)) {
                $sql .= " WHERE {$conditions}";
            }

            $results = $this->fetchAll($sql, $params);
            return array_column($results, $column);
        } catch (PDOException $e) {
            $this->logError("Pluck failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a single row matching conditions in a table.
     * 
     * @param string $table Table name
     * @param string $conditions Optional WHERE clause (without WHERE)
     * @param array $params Parameters for WHERE clause
     * @return array|false Single row or false if not found
     */
    public function find($table, $conditions = "", $params = [])
    {
        try {
            $sql = "SELECT * FROM {$table}";
            if (!empty($conditions)) {
                $sql .= " WHERE {$conditions}";
            }
            $sql .= " LIMIT 1";

            return $this->fetch($sql, $params);
        } catch (PDOException $e) {
            $this->logError("Find failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve paginated data from a table.
     * 
     * @param string $table Table name
     * @param int $page Current page number (default 1)
     * @param int $perPage Items per page (default 10)
     * @param string $conditions Optional WHERE clause (without WHERE)
     * @param array $params Parameters for WHERE clause
     * @return array Pagination data with keys: data, total, per_page, current_page, last_page
     */
    public function paginate($table, $page = 1, $perPage = 10, $conditions = "", $params = [])
    {
        try {
            $offset = ($page - 1) * $perPage;

            // Get total count
            $total = $this->count($table, $conditions, $params);

            // Get paginated data
            $sql = "SELECT * FROM {$table}";
            if (!empty($conditions)) {
                $sql .= " WHERE {$conditions}";
            }
            $sql .= " LIMIT {$offset}, {$perPage}";

            $data = $this->fetchAll($sql, $params);

            return [
                'data' => $data,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            $this->logError("Paginate failed: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 0
            ];
        }
    }

    /**
     * Execute a raw SQL query with parameters.
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for query
     * @return \PDOStatement
     * @throws \Exception
     */
    public function query($sql, $params = [])
    {
        $startTime = microtime(true);

        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);

            $executionTime = (microtime(true) - $startTime) * 1000;

            DebugToolbar::addQuery($sql, $params, $executionTime);

            return $stmt;
        } catch (PDOException $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;

            DebugToolbar::addQuery($sql, $params, $executionTime, false);

            $this->logError("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new \Exception("SQL query error: " . $e->getMessage());
        }
    }

    /**
     * Get the list of tables in the current database.
     * 
     * @return array List of table names
     */
    public function getTables()
    {
        try {
            $sql = "SHOW TABLES";
            $result = $this->fetchAll($sql);
            return array_column($result, 'Tables_in_' . $this->dbname);
        } catch (PDOException $e) {
            $this->logError("Get tables failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the structure of a table (columns info).
     * 
     * @param string $tableName
     * @return array Array of columns info
     */
    public function describeTable($tableName)
    {
        try {
            $sql = "DESCRIBE {$tableName}";
            return $this->fetchAll($sql);
        } catch (PDOException $e) {
            $this->logError("Describe table failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add an index on a table column.
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @return bool Success status
     */
    public function addIndex($table, $column)
    {
        try {
            $indexName = "idx_{$table}_{$column}";
            $sql = "ALTER TABLE {$table} ADD INDEX {$indexName} ({$column})";
            $this->execute($sql);
            return true;
        } catch (PDOException $e) {
            $this->logError("Add index failed: " . $e->getMessage());
            return false;
        }
    }
}
?>
