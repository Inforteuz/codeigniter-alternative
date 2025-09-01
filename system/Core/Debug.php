<?php

namespace System\Core;

use System\Core\Env;

/**
 * Debug Class
 *
 * This class handles error display, memory usage tracking, and query logging.
 * It provides detailed debugging information during development and manages
 * error and exception handling for the application.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System\Core
 * @version    1.0.0
 * @date       2024-12-01
 *
 * @description
 * Main features include:
 *  - Tracking script execution time and memory usage.
 *  - Collecting and storing SQL queries with execution details.
 *  - Capturing and logging PHP errors and exceptions.
 *  - Displaying a detailed debug page when APP_DEBUG is enabled.
 *  - Showing a user-friendly error page in production environment.
 *
 * @methods
 * - `init()`: Initializes debug timers and registers error/exception handlers.
 * - `addQuery($sql, $params = [], $time = 0)`: Logs database queries.
 * - `getExecutionTime()`: Returns the elapsed execution time since init.
 * - `getMemoryUsage()`: Returns current, peak, and initial memory usage.
 * - `getSystemInfo()`: Provides system environment information.
 * - `getQueries()`: Returns the list of logged queries.
 * - `getErrors()`: Returns the list of captured errors and exceptions.
 * - `errorHandler($severity, $message, $file, $line)`: Handles PHP errors.
 * - `exceptionHandler($exception)`: Handles uncaught exceptions.
 * - `shutdownHandler()`: Handles fatal errors on script shutdown.
 * - `showDebugPage()`: Displays detailed debug information page.
 * - `showProductionError()`: Displays a simple error page for production.
 * - `formatBytes($bytes, $precision = 2)`: Formats bytes into human-readable form.
 *
 * @example
 * ```php
 * // Initialize debugging at the start of your script
 * \System\Core\Debug::init();
 * 
 * // Add a query log entry
 * \System\Core\Debug::addQuery('SELECT * FROM users WHERE id = ?', [1], 12.5);
 * 
 * // Get execution time and memory usage for reporting
 * $time = \System\Core\Debug::getExecutionTime();
 * $memory = \System\Core\Debug::getMemoryUsage();
 * ```
 */
class Debug
{
    private static $startTime;
    private static $startMemory;
    private static $queries = [];
    private static $errors = [];
    
    public static function init()
    {
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage();
        
        // Register custom error and exception handlers
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        register_shutdown_function([self::class, 'shutdownHandler']);
    }
    
    public static function addQuery($sql, $params = [], $time = 0)
    {
        self::$queries[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
    }
    
    public static function getExecutionTime()
    {
        if (self::$startTime === null) {
            return 0;
        }
        return round((microtime(true) - self::$startTime) * 1000, 2);
    }
    
    public static function getMemoryUsage()
    {
        $startMemory = self::$startMemory ?? memory_get_usage();
        
        return [
            'current' => self::formatBytes(memory_get_usage()),
            'peak' => self::formatBytes(memory_get_peak_usage()),
            'start' => self::formatBytes($startMemory)
        ];
    }
    
    public static function getSystemInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'loaded_extensions' => get_loaded_extensions(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];
    }
    
    public static function getQueries()
    {
        return self::$queries;
    }
    
    public static function getErrors()
    {
        return self::$errors;
    }
    
    public static function errorHandler($severity, $message, $file, $line)
    {
        self::$errors[] = [
            'type' => 'Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'time' => date('Y-m-d H:i:s')
        ];
        
        // Show debug page if APP_DEBUG is enabled
        if (Env::get('APP_DEBUG') === 'true') {
            self::showDebugPage();
        }
        
        return true;
    }
    
    public static function exceptionHandler($exception)
    {
        self::$errors[] = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'time' => date('Y-m-d H:i:s')
        ];
        
        if (Env::get('APP_DEBUG') === 'true') {
            self::showDebugPage();
        } else {
            self::showProductionError();
        }
    }
    
    public static function shutdownHandler()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::$errors[] = [
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'time' => date('Y-m-d H:i:s')
            ];
            
            if (Env::get('APP_DEBUG') === 'true') {
                self::showDebugPage();
            } else {
                self::showProductionError();
            }
        }
    }
    
    public static function showDebugPage()
    {
        $executionTime = self::getExecutionTime();
        $memoryUsage = self::getMemoryUsage();
        $systemInfo = self::getSystemInfo();
        $queries = self::$queries;
        $errors = self::$errors;
        
        $debugViewPath = __DIR__ . '/../../app/Views/debug/debug.php';
        
        if (file_exists($debugViewPath)) {
            include $debugViewPath;
        } else {
            self::showProductionError();
        }
        exit;
    }
    
    private static function showProductionError()
    {
        http_response_code(500);
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Server Error</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .error { background: #f8f8f8; padding: 30px; border-radius: 10px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='error'>
                <h1>500 - Server Error</h1>
                <p>Something went wrong. Please try again later.</p>
            </div>
        </body>
        </html>";
        exit;
    }
    
    private static function formatBytes($bytes, $precision = 2)
    {
        if ($bytes === null || !is_numeric($bytes)) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
?>