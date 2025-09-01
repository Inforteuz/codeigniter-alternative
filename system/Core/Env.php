<?php
namespace System\Core;

/**
 * Env Class
 *
 * This class is responsible for loading, accessing, and managing environment variables
 * from a .env file and the server environment. It provides methods to load environment
 * variables into PHP's runtime, retrieve them with defaults, and set new variables dynamically.
 *
 * Main features:
 *  - Load .env file from common paths and parse its contents.
 *  - Store environment variables internally and in PHP's environment arrays ($_ENV, getenv).
 *  - Retrieve single or all environment variables with optional default values.
 *  - Set environment variables at runtime.
 *  - Check if environment variables have already been loaded.
 *  - Provide convenient accessors for database and application configuration values.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System\Core
 * @version    1.0.0
 * @date       2024-12-01
 *
 * @methods
 * - `load($path = null)`: Loads and parses the .env file from specified or default locations.
 * - `get($key, $default = null)`: Retrieves the value of an environment variable or returns a default.
 * - `getAll()`: Returns all loaded environment variables.
 * - `set($key, $value)`: Sets or overrides an environment variable.
 * - `isLoaded()`: Returns true if .env variables have been loaded.
 * - `getDatabaseConfig()`: Returns an array of database configuration parameters.
 * - `getAppConfig()`: Returns an array of application configuration parameters.
 *
 * @example
 * ```php
 * // Load environment variables from .env file
 * \System\Core\Env::load();
 * 
 * // Get a specific environment variable with default
 * $dbHost = \System\Core\Env::get('DB_HOST', 'localhost');
 * 
 * // Set a new environment variable
 * \System\Core\Env::set('NEW_VAR', 'value');
 * 
 * // Check if env is loaded
 * if (\System\Core\Env::isLoaded()) {
 *     // Do something
 * }
 * ```
 */
class Env
{
    private static $loaded = false;
    private static $envData = [];

    public static function load($path = null)
    {
        if (self::$loaded) {
            return true;
        }

        if ($path === null) {
            $path = self::findEnvFile();
        }
        
        if (!file_exists($path)) {
            error_log(".env file not found at: " . $path);
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
                continue;
            }

            $line = trim($line);
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                self::$envData[$name] = $value;
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
        
        self::$loaded = true;
        return true;
    }

    private static function findEnvFile()
    {
        $possiblePaths = [
            __DIR__ . '/../../.env',
            __DIR__ . '/../../../.env',
            getcwd() . '/.env',
            realpath(__DIR__ . '/../../') . '/.env'
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return realpath(__DIR__ . '/../../') . '/.env';
    }

    public static function get($key, $default = null)
    {
        if (isset(self::$envData[$key])) {
            return self::$envData[$key];
        }
        
        $value = getenv($key);
        
        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }
        
        if ($value === false && isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        }
        
        return $value !== false ? $value : $default;
    }

    public static function getAll()
    {
        $envVars = [];
        
        foreach ($_ENV as $key => $value) {
            $envVars[$key] = $value;
        }
        
        return $envVars;
    }

    public static function set($key, $value)
    {
        $_ENV[$key] = $value;
        putenv("$key=$value");
        self::$envData[$key] = $value;
        return true;
    }

    public static function isLoaded()
    {
        return self::$loaded;
    }

    public static function getDatabaseConfig()
    {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'database' => self::get('DB_NAME', 'educrm'),
            'username' => self::get('DB_USER', 'root'),
            'password' => self::get('DB_PASS', ''),
            'charset' => self::get('DB_CHARSET', 'utf8mb4')
        ];
    }

    public static function getAppConfig()
    {
        return [
            'name' => self::get('APP_NAME', 'EduCRM'),
            'env' => self::get('APP_ENV', 'production'),
            'debug' => self::get('APP_DEBUG', 'false') === 'true',
            'url' => self::get('APP_URL', 'http://localhost'),
            'timezone' => self::get('TIMEZONE', 'Asia/Tashkent')
        ];
    }
}
?>