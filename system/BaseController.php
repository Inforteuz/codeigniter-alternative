<?php

/**
 * BaseController.php
 *
 * This file provides a base controller class that provides common functionality and basic capabilities for creating other controllers
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    1.0.0
 * @date       2024-12-01
 * 
* @description
* This class performs the following main functions:
*
* 1. **Session Management**:
* - Automatically starts a session when the class is started.
* 
* 2. **Redirect**:
* - `to($url)` - redirects the user to the given URL.
* - `base_url($path)` - appends a relative path to the base URL of the site.
* 
* 3. **Message Filtering**:
* - `filterMessage($message)` - removes special characters to secure the data entered by the user.
*
* 4. **Logging**:
* - `logError($message)` - stores errors that occurred in the system in the daily log files.
*
* 5. **Load View**:
* - `view($view, $data)` - loads the given view file and passes data to it.
* - If the view file is not found, it will show a 500 error page.
*
* 6. **Show errors**:
* - `showError($title, $message)` - shows a nice error page to the user.
* - `show404()` - returns a 404 error (page not found) page.
* - `show500($message)` - returns a 500 error (internal server error) page.
*
* 7. **Generate unique user ID**:
* - `generateUserId()` - generates a unique and random ID for each user.
*
* @class BaseController
 * 
* @methods
* - `__construct()`: Initialize the class and start the session.
* - `to($url)`: Redirect the user to another URL.
* - `redirect()`: Return a ready-made object for redirection (chainable redirect).
* - `base_url($path)`: Create a path relative to the base URL of the site.
* - `filterMessage($message)`: Transform the entered data into a secure form.
* - `logError($message)`: Write system errors to the log file.
* - `view($view, $data)`: Load the view file and transfer data.
* - `showError($title, $message)`: Show an error page to the user.
* - `show404()`: Show a 404 error page.
* - `show500($message)`: Show the 500 error page.
* - `generateUserId()`: Generate a unique user ID.
* - `showDebugInfo()`: Show the Debug page.
* - `getMemoryUsage()`: Get memory usage information.
* - `getExecutionTime()`: Get execution time information.
*
* This class serves as the base for all controllers in your MVC framework.
*/

namespace System;

use System\Core\Env;
use System\Database\Database;
use System\Core\Debug;

class BaseController
{
    protected $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        Env::load();
        
        $this->db = Database::getInstance();
    }

    public function to($url)
    {
        header("Location: $url");
        exit();
    }

    public function redirect()
    {
        return $this; 
    }

    public function base_url($path = '')
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
    }

    public function filterMessage($message)
    {
        $pattern = '/[\"<>\/*\&\%\$\#$$$$\[\]\{\}]/';

        $cleanedMessage = preg_replace($pattern, '', $message);

        $cleanedMessage = str_replace(["'", '`'], "‘", $cleanedMessage);

        if ($cleanedMessage !== $message) {
            return $cleanedMessage;
        }

        return $message;
    }

    protected function logError($message)
    {
        $logDir = __DIR__ . '/../writable/logs';
        date_default_timezone_set("Asia/Tashkent");
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        $dateTime = date('Y-m-d H:i:s');
        $logMessage = "[{$dateTime}] ERROR: {$message}\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    protected function logDebug($message)
    {
        $logDir = __DIR__ . '/../writable/logs';
        
        date_default_timezone_set("Asia/Tashkent");
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
        
        $dateTime = date('Y-m-d H:i:s');
        $logMessage = "[{$dateTime}] DEBUG: {$message}\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    public function uploadFile($fileInputName, $allowedExtensions = [], $maxFileSize = 10485760, $folder = '')
    {
        if (!isset($_FILES[$fileInputName])) {
            $this->logError('No file uploaded.');
            return ['error' => 'No file uploaded.'];
        }

        $file = $_FILES[$fileInputName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->logError('Error during file upload. Error code: ' . $file['error']);
            return ['error' => 'Error during file upload.'];
        }

        if ($file['size'] > $maxFileSize) {
            $this->logError('File is too large. File size: ' . $file['size']);
            return ['error' => 'File is too large.'];
        }

        if (!empty($allowedExtensions)) {
            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);
            if (!in_array($extension, $allowedExtensions)) {
                $this->logError('Invalid file type. Allowed extensions: ' . implode(', ', $allowedExtensions) . '. Uploaded extension: ' . $extension);
                return ['error' => 'Invalid file type.'];
            }
        }

        $uploadDir = __DIR__ . '/../writable/uploads/';
        if ($folder) {
            $uploadDir .= $folder . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
        }

        $encryptedFileName = md5(uniqid(rand(), true)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $destinationPath = $uploadDir . $encryptedFileName;

        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            $this->logError('Failed to move uploaded file. File: ' . $file['name']);
            return ['error' => 'Failed to move uploaded file'];
        }

        return [
            'success' => true,
            'fileName' => $encryptedFileName,
            'filePath' => $destinationPath,
            'originalName' => $file['name'],
            'fileSize' => $file['size'],
            'folder' => $folder
        ];
    }

    protected function view($view, $data = [])
    {
        try {
            extract($data);
            $viewFile = "app/Views/{$view}.php";
            if (file_exists($viewFile)) {
                require_once $viewFile;
            } else {
                throw new \Exception("View file \"{$view}.php\" not found.");
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage()); 
            $this->showError(500, $e->getMessage());
        }
    }

    /**
    * Displays the error and writes it to the log.
    * 
    * @param int $code Error code (e.g. 404)
    * @param string $message Detailed information about the error
    */
    private function showError($code, $message)
    {
        http_response_code($code);

        $errorFile = __DIR__ . "/../app/Views/errors/{$code}.php";
        
        if (file_exists($errorFile)) {
            include($errorFile);
            
            $this->logError("{$code} {$message}");
            return;
        }

        $this->logError("{$code} {$message}");
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <link rel='icon' href='favicon.ico' type='image/png'>
            <title>{$code} - Xatolik</title>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f5f5f5;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    color: #333;
                }

                .error-container {
                    background-color: #fff;
                    padding: 30px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    max-width: 420px;
                    width: 100%;
                }

                .error-container h1 {
                    font-size: 60px;
                    color: #e74c3c;
                    margin-bottom: 10px;
                }

                .error-container h2 {
                    font-size: 20px;
                    color: #555;
                    margin-bottom: 15px;
                }

                .error-container p {
                    font-size: 14px;
                    color: #777;
                    margin-bottom: 20px;
                }

                .error-container .button {
                    text-decoration: none;
                    background-color: #3498db;
                    color: #fff;
                    font-size: 14px;
                    padding: 8px 18px;
                    border-radius: 5px;
                    display: inline-block;
                    transition: background-color 0.3s ease;
                }

                .error-container .button:hover {
                    background-color: #2980b9;
                }

                .error-container .icon {
                    font-size: 60px;
                    color: #e74c3c;
                    margin-bottom: 15px;
                }

                @media (max-width: 600px) {
                    .error-container h1 {
                        font-size: 50px;
                    }

                    .error-container h2 {
                        font-size: 18px;
                    }

                    .error-container p {
                        font-size: 12px;
                    }

                    .error-container .button {
                        padding: 6px 12px;
                        font-size: 12px;
                    }

                    .error-container .icon {
                        font-size: 50px;
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
                <p>This page does not exist or the request was made incorrectly.</p>
                <a href='/' class='button'>Return to home page</a>
            </div>
        </body>
        </html>
        ";
    }

    /**
    * Debugger: Show variables nicely
    *
    * @param mixed $data - Variable being checked
    * @param bool $stop - if true the script stops (default: true)
    */

    public function dd($data, $stop = true)
    {
        echo "<pre style='background-color: #222; color: #0f0; padding: 15px; border: 1px solid #333; border-radius: 5px; font-family: monospace;'>"; 
        echo "<strong>Debugging Output:</strong>\n\n";
        print_r($data);
        echo "</pre>";
        
        $this->logDebug(print_r($data, true));
        
        if ($stop) {
            die;
        }
    }

    public function show404()
    {
        header("HTTP/1.1 404 Not Found");
        $this->logError("404 Not Found - The page you are looking for could not be found.");
        $this->view('errors/404');
    }

    public function show500($message)
    {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        $this->logError("500 Internal Server Error - {$message}");
        $this->showError(500, $message);
    }

    /**
    * Cache function.
    * 
    * This function stores the given key and value in the cache as a file.
    * If the cache exists, it returns its value.
    *
    * @param string $key - Cache key
    * @param mixed $data - Data to be cached
    * @param int $duration - Cache duration (in seconds)
    * @return mixed
    */
    public function cache($key, $data = null, $duration = 3600)
    {
        $cacheDir = __DIR__ . '/../writable/cache/';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $cacheFile = $cacheDir . md5($key) . '.cache';

        if ($data === null) {
            if (file_exists($cacheFile) && (filemtime($cacheFile) + $duration > time())) {
                return unserialize(file_get_contents($cacheFile));
            }
            return null;
        }

        file_put_contents($cacheFile, serialize($data));
        return true;
    }

    /**
    * Generate a unique user_id.
    * 
    * Generates a unique and random ID for each user.
    * 
    * @return string
    */
    protected function generateUserId()
    {
        return uniqid('USER-');
    }
    
    /**
    * Return JSON response.
    */
    public function jsonResponse($data, $status = 200)
    {
        header("Content-Type: application/json");
        http_response_code($status);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Retrieve and return POST data securely.
     */
    public function inputPost($key)
    {
        return isset($_POST[$key]) ? htmlspecialchars(trim($_POST[$key])) : null;
    }

    /**
     * Retrieve and return GET data securely.
     */
    public function inputGet($key)
    {
        return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key])) : null;
    }
    
    /**
     * Clear inputs from special characters.
     */
    public function sanitizeInput($input, $type = 'string')
    {
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Set flash message.
     */
    public function setFlashMessage($key, $message)
    {
        $_SESSION['flash_messages'][$key] = $message;
    }

    /**
     * Show flash message.
     */
    public function getFlashMessage($key)
    {
        if (isset($_SESSION['flash_messages'][$key])) {
            $message = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $message;
        }
        return null;
    }
    
    /**
     * Set flash messages
     */
    protected function setFlash($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][$type] = $message;
    }
    
    /**
     * Read flash messages
     */
    protected function getFlash($type)
    {
        if (isset($_SESSION['flash_messages'][$type])) {
            $message = $_SESSION['flash_messages'][$type];
            unset($_SESSION['flash_messages'][$type]);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Check if user has the required role
     */
    protected function hasRole($requiredRole)
    {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        
        if (is_array($requiredRole)) {
            return in_array($_SESSION['role'], $requiredRole);
        }
        
        return $_SESSION['role'] === $requiredRole;
    }
    
    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
    }

    /**
     * Show Debug page
     */
    public function showDebugInfo()
    {
        if (Env::get('APP_DEBUG', false)) {
            Debug::showDebugPage();
        } else {
            $this->show404();
        }
    }

    /**
     * Get memory usage information
     */
    public function getMemoryUsage()
    {
        return Debug::getMemoryUsage();
    }

    /**
     * Get execution time information
     */
    public function getExecutionTime()
    {
        return Debug::getExecutionTime();
    }

}
?>