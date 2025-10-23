<?php

/**
 * ErrorHandler.php
 * 
 * Error Handler Class - Handles errors and exceptions in production environment
 * This class provides comprehensive error handling for the framework including:
 * - Error logging to daily files
 * - User-friendly error pages in production
 * - Support for all PHP error types
 * - Custom error page rendering
 * 
 * @package    System
 * @category   Core
 * @author     Inforte
 * @version    1.0.0
 * @since      2024-01-01
 */

namespace System;

/**
 * ErrorHandler class - Manages errors and exceptions in production environment
 */
class ErrorHandler
{
    /**
     * Register error handlers
     * 
     * Sets up custom error, exception, and shutdown handlers
     * to replace PHP's default error handling
     */
    public static function register()
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     * 
     * @param int $severity Error severity level
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line number where error occurred
     * @return bool Always returns true to prevent default error handler
     */
    public static function handleError($severity, $message, $file, $line)
    {
        // If error reporting is disabled for this severity, ignore
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $error = [
            'type' => 'Error',
            'severity' => self::getErrorType($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'time' => date('Y-m-d H:i:s')
        ];

        // Log error to file
        self::logError($error);

        // Show production error page to user
        self::showProductionError(500);

        return true;
    }

    /**
     * Handle exceptions
     * 
     * @param \Exception $exception The exception object
     * @return bool Always returns true
     */
    public static function handleException($exception)
    {
        $error = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'time' => date('Y-m-d H:i:s')
        ];

        // Log exception
        self::logError($error);

        // Show production error page
        self::showProductionError(500);

        return true;
    }

    /**
     * Handle shutdown errors (fatal errors)
     */
    public static function handleShutdown()
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'Fatal Error',
                'severity' => self::getErrorType($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'time' => date('Y-m-d H:i:s')
            ];

            // Log fatal error
            self::logError($errorData);

            // Show production error page
            self::showProductionError(500);
        }
    }

    /**
     * Convert error type constant to readable string
     * 
     * @param int $type PHP error type constant
     * @return string Human-readable error type
     */
    private static function getErrorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1
                return 'E_ERROR';
            case E_WARNING: // 2
                return 'E_WARNING';
            case E_PARSE: // 4
                return 'E_PARSE';
            case E_NOTICE: // 8
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384
                return 'E_USER_DEPRECATED';
            default:
                return 'Unknown error type: ' . $type;
        }
    }

    /**
     * Log error to file
     * 
     * @param array $error Error data array
     */
    private static function logError($error)
    {
        $logDir = __DIR__ . '/../writable/logs';
        
        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        $dateTime = date('Y-m-d H:i:s');
        
        $logMessage = "[{$dateTime}] ";
        $logMessage .= "{$error['type']}: ";
        $logMessage .= "{$error['message']} in {$error['file']}:{$error['line']}";
        
        if (isset($error['severity'])) {
            $logMessage .= " [Severity: {$error['severity']}]";
        }
        
        $logMessage .= "\n";
        
        // Add stack trace if available
        if (isset($error['trace'])) {
            $logMessage .= "Stack trace:\n{$error['trace']}\n";
        }
        
        $logMessage .= "================================================\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Show production error page to user
     * 
     * @param int $code HTTP status code
     */
    public static function showProductionError($code = 500)
    {
        http_response_code($code);
        
        // Check if custom error page exists
        $errorFile = __DIR__ . "/../app/Views/errors/{$code}.php";
        
        if (file_exists($errorFile)) {
            include $errorFile;
            exit;
        }
        
        // Show default error page
        self::renderErrorPage($code);
        exit;
    }

    /**
     * Render default error page
     * 
     * @param int $code HTTP status code
     */
    private static function renderErrorPage($code)
    {
        $messages = [
            400 => "Bad Request",
            401 => "Unauthorized",
            403 => "Forbidden",
            404 => "Page Not Found",
            500 => "Internal Server Error",
            503 => "Service Unavailable"
        ];

        $message = $messages[$code] ?? "An error occurred";

        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$code} - {$message}</title>
            <link rel='preconnect' href='https://fonts.googleapis.com'>
            <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
            <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap' rel='stylesheet'>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Inter', sans-serif;
                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    color: #212529;
                }

                .error-container {
                    background-color: #ffffff;
                    padding: 40px 30px;
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    max-width: 450px;
                    width: 90%;
                    animation: fadeIn 0.5s ease-in-out;
                }

                .error-container h1 {
                    font-size: 72px;
                    color: #dc3545;
                    margin-bottom: 10px;
                    font-weight: 700;
                }

                .error-container h2 {
                    font-size: 24px;
                    margin-bottom: 10px;
                    color: #495057;
                    font-weight: 600;
                }

                .error-container p {
                    font-size: 15px;
                    color: #6c757d;
                    margin-bottom: 25px;
                }

                .error-container .button {
                    text-decoration: none;
                    background: linear-gradient(to right, #007bff, #6610f2);
                    color: #fff;
                    font-size: 14px;
                    padding: 10px 20px;
                    border-radius: 6px;
                    display: inline-block;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
                }

                .error-container .button:hover {
                    background: linear-gradient(to right, #0056b3, #520dc2);
                    box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
                    transform: translateY(-2px);
                }

                .error-container .icon {
                    font-size: 60px;
                    color: #dc3545;
                    margin-bottom: 20px;
                }

                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: scale(0.95);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }

                @media (max-width: 600px) {
                    .error-container h1 {
                        font-size: 52px;
                    }

                    .error-container h2 {
                        font-size: 18px;
                    }

                    .error-container p {
                        font-size: 13px;
                    }

                    .error-container .button {
                        padding: 8px 16px;
                        font-size: 13px;
                    }

                    .error-container .icon {
                        font-size: 48px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='icon'>
                    <i class='fas fa-exclamation-triangle'></i>
                </div>
                <h1>{$code}</h1>
                <h2>{$message}</h2>
                <p>Sorry, an error occurred while processing your request.</p>
                <a href='/' class='button'>Return to Home Page</a>
            </div>
        </body>
        </html>";
    }
}
?>
