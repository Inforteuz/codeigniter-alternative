<?php
/**
 * BaseController.php
 *
 * This file provides the enhanced base controller class with CodeIgniter 4 inspired features.
 * It serves as the foundation for all other controllers with extended functionality.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    2.0.0
 * @date       2025-01-01
 *
 * @description
 * Enhanced functionality includes:
 *
 * 1. **Request & Response Handling**:
 *    - Enhanced input methods with validation
 *    - Response formatting and status code management
 *    - Header management utilities
 *
 * 2. **Model & Helper Loading**:
 *    - Dynamic model loading and caching
 *    - Helper function loading system
 *
 * 3. **Advanced Validation**:
 *    - Rule-based validation system
 *    - Custom validation rules
 *    - Validation error handling
 *
 * 4. **Cookie & Session Management**:
 *    - Secure cookie handling
 *    - Enhanced session utilities
 *
 * 5. **URL & Routing Helpers**:
 *    - URL generation and manipulation
 *    - Route parameter handling
 *
 * 6. **Security Features**:
 *    - CSRF token management
 *    - XSS protection
 *    - SQL injection prevention
 */
namespace System;
use System\Core\Env;
use System\Database\Database;
use System\Core\Debug;

class BaseController
{
    protected $db;
    protected $base_url;
    protected $models = [];
    protected $helpers = [];
    protected $request = [];
    protected $validationRules = [];
    protected $validationErrors = [];

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        Env::load();
        $this->db = Database::getInstance();
        $this->base_url = $this->getBaseUrl();
        $this->initializeRequest();
        $this->initializeCSRF();
    }

    /**
     * Initialize request data
     */
    private function initializeRequest()
    {
        $this->request = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'get' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
            'headers' => getallheaders() ?: [],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->getClientIpAddress()
        ];
    }

    /**
     * Initialize CSRF protection
     */
    private function initializeCSRF()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    // ===== REQUEST METHODS =====

    /**
     * Get request method
     * @return string
     */
    public function getMethod()
    {
        return $this->request['method'];
    }

    /**
     * Check if request method matches
     * @param string $method
     * @return bool
     */
    public function isMethod($method)
    {
        return strtoupper($this->request['method']) === strtoupper($method);
    }

    /**
     * Get all POST data or specific key
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get all GET data or specific key
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getGet($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get input data from any method (GET, POST, PUT, etc.)
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getVar($key, $default = null)
    {
        // Check POST first
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        // Then GET
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        
        // Then php://input for PUT, PATCH, DELETE
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input) && isset($input[$key])) {
            return $input[$key];
        }
        
        return $default;
    }

    /**
     * Get JSON input data
     * @param bool $assoc
     * @return mixed
     */
    public function getJSON($assoc = true)
    {
        $input = file_get_contents('php://input');
        return json_decode($input, $assoc);
    }

    /**
     * Get client IP address
     * @return string
     */
    public function getClientIpAddress()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get request header
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        $headers = $this->request['headers'];
        $name = strtolower($name);
        
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }
        
        return $default;
    }

    // ===== RESPONSE METHODS =====

    /**
     * Set response header
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return self
     */
    public function setHeader($name, $value, $replace = true)
    {
        header("$name: $value", $replace);
        return $this;
    }

    /**
     * Set response status code
     * @param int $code
     * @return self
     */
    public function setStatusCode($code)
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Send JSON response with data
     * @param mixed $data
     * @param int $statusCode
     * @param array $headers
     * @return never
     */
    public function respondWithJSON($data, $statusCode = 200, $headers = [])
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send created response (201)
     * @param mixed $data
     * @return never
     */
    public function respondCreated($data = null)
    {
        $response = ['status' => 'created', 'message' => 'Resource created successfully'];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->respondWithJSON($response, 201);
    }

    /**
     * Send no content response (204)
     * @return never
     */
    public function respondNoContent()
    {
        $this->setStatusCode(204);
        exit;
    }

    /**
     * Send bad request response (400)
     * @param string $message
     * @return never
     */
    public function respondBadRequest($message = 'Bad Request')
    {
        $this->respondWithJSON([
            'status' => 'error',
            'message' => $message
        ], 400);
    }

    /**
     * Send unauthorized response (401)
     * @param string $message
     * @return never
     */
    public function respondUnauthorized($message = 'Unauthorized')
    {
        $this->respondWithJSON([
            'status' => 'error',
            'message' => $message
        ], 401);
    }

    /**
     * Send forbidden response (403)
     * @param string $message
     * @return never
     */
    public function respondForbidden($message = 'Forbidden')
    {
        $this->respondWithJSON([
            'status' => 'error',
            'message' => $message
        ], 403);
    }

    /**
     * Send not found response (404)
     * @param string $message
     * @return never
     */
    public function respondNotFound($message = 'Not Found')
    {
        $this->respondWithJSON([
            'status' => 'error',
            'message' => $message
        ], 404);
    }

    /**
     * Send internal server error response (500)
     * @param string $message
     * @return never
     */
    public function respondInternalError($message = 'Internal Server Error')
    {
        $this->respondWithJSON([
            'status' => 'error',
            'message' => $message
        ], 500);
    }

    // ===== MODEL & HELPER LOADING =====

    /**
     * Load and instantiate a model
     * @param string $modelName
     * @param string|null $alias
     * @return object
     */
    public function model($modelName, $alias = null)
    {
        $alias = $alias ?: $modelName;
        
        if (isset($this->models[$alias])) {
            return $this->models[$alias];
        }
        
        $modelClass = "\\App\\Models\\{$modelName}";
        
        if (!class_exists($modelClass)) {
            throw new \Exception("Model {$modelName} not found");
        }
        
        $this->models[$alias] = new $modelClass();
        return $this->models[$alias];
    }

    /**
     * Load a helper
     * @param string $helperName
     * @return bool
     */
    public function helper($helperName)
    {
        if (in_array($helperName, $this->helpers)) {
            return true;
        }
        
        $helperFile = __DIR__ . "/../app/Helpers/{$helperName}_helper.php";
        
        if (file_exists($helperFile)) {
            require_once $helperFile;
            $this->helpers[] = $helperName;
            return true;
        }
        
        $this->logError("Helper {$helperName} not found");
        return false;
    }

    // ===== VALIDATION METHODS =====

    /**
     * Set validation rules
     * @param array $rules
     * @return self
     */
    public function setValidationRules($rules)
    {
        $this->validationRules = $rules;
        return $this;
    }

    /**
     * Validate data against rules
     * @param array $data
     * @param array|null $rules
     * @return bool
     */
    public function validate($data, $rules = null)
    {
        $rules = $rules ?: $this->validationRules;
        $this->validationErrors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleList = is_string($rule) ? explode('|', $rule) : $rule;
            
            foreach ($ruleList as $singleRule) {
                if (!$this->validateField($field, $value, $singleRule)) {
                    break; // Stop on first error for this field
                }
            }
        }
        
        return empty($this->validationErrors);
    }

    /**
     * Validate single field
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return bool
     */
    private function validateField($field, $value, $rule)
    {
        $parts = explode('[', $rule, 2);
        $ruleName = $parts[0];
        $parameter = isset($parts[1]) ? rtrim($parts[1], ']') : null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    $this->validationErrors[$field][] = "The {$field} field is required.";
                    return false;
                }
                break;
                
            case 'min_length':
                if (strlen($value) < (int)$parameter) {
                    $this->validationErrors[$field][] = "The {$field} field must be at least {$parameter} characters.";
                    return false;
                }
                break;
                
            case 'max_length':
                if (strlen($value) > (int)$parameter) {
                    $this->validationErrors[$field][] = "The {$field} field cannot exceed {$parameter} characters.";
                    return false;
                }
                break;
                
            case 'valid_email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->validationErrors[$field][] = "The {$field} field must contain a valid email address.";
                    return false;
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->validationErrors[$field][] = "The {$field} field must contain only numbers.";
                    return false;
                }
                break;
                
            case 'integer':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->validationErrors[$field][] = "The {$field} field must contain an integer.";
                    return false;
                }
                break;
                
            case 'alpha':
                if (!ctype_alpha($value)) {
                    $this->validationErrors[$field][] = "The {$field} field may only contain alphabetical characters.";
                    return false;
                }
                break;
                
            case 'alpha_numeric':
                if (!ctype_alnum($value)) {
                    $this->validationErrors[$field][] = "The {$field} field may only contain alpha-numeric characters.";
                    return false;
                }
                break;
        }
        
        return true;
    }

    /**
     * Get validation errors
     * @param string|null $field
     * @return array
     */
    public function getValidationErrors($field = null)
    {
        if ($field !== null) {
            return $this->validationErrors[$field] ?? [];
        }
        return $this->validationErrors;
    }

    // ===== COOKIE METHODS =====

    /**
     * Set a cookie
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return bool
     */
    public function setCookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = true)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Get a cookie value
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getCookie($name, $default = null)
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * Delete a cookie
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return bool
     */
    public function deleteCookie($name, $path = '/', $domain = '')
    {
        return setcookie($name, '', time() - 3600, $path, $domain);
    }

    // ===== SECURITY METHODS =====

    /**
     * Generate CSRF token
     * @return string
     */
    public function generateCSRFToken()
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Get CSRF token
     * @return string
     */
    public function getCSRFToken()
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Verify CSRF token
     * @param string $token
     * @return bool
     */
    public function verifyCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * XSS Clean
     * @param mixed $data
     * @return mixed
     */
    public function xssClean($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'xssClean'], $data);
        }
        
        // Remove potential XSS vectors
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $data);
        $data = str_replace(['<script', '</script>', 'javascript:', 'onclick=', 'onerror='], '', $data);
        
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // ===== URL HELPER METHODS =====

    /**
     * Create URL from segments
     * @param string $segments
     * @param array $params
     * @return string
     */
    public function url($segments = '', $params = [])
    {
        $url = $this->base_url($segments);
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Create site URL
     * @param string $uri
     * @return string
     */
    public function site_url($uri = '')
    {
        return $this->base_url($uri);
    }

    /**
     * Get current URL
     * @return string
     */
    public function current_url()
    {
        return $this->base_url($_SERVER['REQUEST_URI'] ?? '');
    }

    /**
     * Get previous URL from referer
     * @return string
     */
    public function previous_url()
    {
        return $_SERVER['HTTP_REFERER'] ?? $this->base_url();
    }

    // ===== PAGINATION HELPER =====

    /**
     * Generate pagination links
     * @param int $totalRecords
     * @param int $perPage
     * @param int $currentPage
     * @param string $baseUrl
     * @return array
     */
    public function paginate($totalRecords, $perPage = 10, $currentPage = 1, $baseUrl = '')
    {
        $totalPages = ceil($totalRecords / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        
        $pagination = [
            'total_records' => $totalRecords,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
            'offset' => ($currentPage - 1) * $perPage,
            'links' => []
        ];
        
        // Generate page links
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pagination['links'][] = [
                'page' => $i,
                'url' => $baseUrl . '?page=' . $i,
                'is_current' => $i === $currentPage
            ];
        }
        
        return $pagination;
    }

    // ===== ENHANCED SESSION METHODS =====

    /**
     * Set session data with optional encryption
     * @param string|array $key
     * @param mixed $value
     * @param bool $encrypt
     * @return void
     */
    public function setSession($key, $value = null, $encrypt = false)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $encrypt ? $this->encrypt($v) : $v;
            }
        } else {
            $_SESSION[$key] = $encrypt ? $this->encrypt($value) : $value;
        }
    }

    /**
     * Get session data with optional decryption
     * @param string $key
     * @param mixed $default
     * @param bool $decrypt
     * @return mixed
     */
    public function getSession($key = null, $default = null, $decrypt = false)
    {
        if ($key === null) {
            return $_SESSION;
        }
        
        $value = $_SESSION[$key] ?? $default;
        
        return $decrypt && $value !== $default ? $this->decrypt($value) : $value;
    }

    /**
     * Remove session data
     * @param string|array $key
     * @return void
     */
    public function unsetSession($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                unset($_SESSION[$k]);
            }
        } else {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Basic encryption (for demo purposes - use proper encryption in production)
     * @param string $data
     * @return string
     */
    private function encrypt($data)
    {
        return base64_encode($data);
    }

    /**
     * Basic decryption (for demo purposes - use proper decryption in production)
     * @param string $data
     * @return string
     */
    private function decrypt($data)
    {
        return base64_decode($data);
    }

    // ===== ORIGINAL METHODS (PRESERVED) =====

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
        return $this->getBaseUrl() . ltrim($path, '/');
    }

    protected function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($script);

        $baseDir = str_replace('\\', '/', $baseDir);

        if ($baseDir !== '/') {
            $baseDir .= '/';
        }

        return $protocol . '://' . $host . $baseDir;
    }

    public function filterMessage($message)
    {
        $pattern = '/[\"<>\/*\&\%\$\#$$$$\[\]\{\}]/';
        $cleanedMessage = preg_replace($pattern, '', $message);
        $cleanedMessage = str_replace(["'", '`'], "'", $cleanedMessage);
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

    protected function renderView(string $viewPath, array $data = []): void
    {
        if (property_exists($this, 'settings') && is_array($this->settings)) {
            $data['settings'] = $this->settings;
        }

        $this->view($viewPath, $data);
    }

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
            <title>{$code} - Error</title>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css  '>
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

    public function dd($data, $stop = true)
    {
        echo "<pre style='background-color: #222; color: #0f0; padding: 15px; border: 1px solid #333; border-radius: 5px; font-family: monospace;'>";
        echo "<strong>Debugging output:</strong>\n";
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

    protected function generateUserId()
    {
        return uniqid('USER-');
    }

    public function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function inputPost($key)
    {
        return isset($_POST[$key]) ? htmlspecialchars(trim($_POST[$key])) : null;
    }

    public function inputGet($key)
    {
        return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key])) : null;
    }

    public function sanitizeInput($input, $type = 'string')
    {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInput($item, $type);
            }, $input);
        }
        $input = trim($input);
        $input = stripslashes($input);

        switch ($type) {
            case 'email':
                $input = filter_var($input, FILTER_SANITIZE_EMAIL);
                break;
            case 'int':
                $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                break;
            case 'float':
                $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                break;
            case 'url':
                $input = filter_var($input, FILTER_SANITIZE_URL);
                break;
            default:
                break;
        }
        return $input;
    }

    public function setFlashMessage($key, $message)
    {
        $_SESSION['flash_messages'][$key] = $message;
    }

    public function getFlashMessage($key)
    {
        if (isset($_SESSION['flash_messages'][$key])) {
            $message = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $message;
        }
        return null;
    }

    protected function setFlash($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][$type] = $message;
    }

    protected function getFlash($type)
    {
        if (isset($_SESSION['flash_messages'][$type])) {
            $message = $_SESSION['flash_messages'][$type];
            unset($_SESSION['flash_messages'][$type]);
            return $message;
        }
        return null;
    }

    protected function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function validateRequired($fields, $data)
    {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "The $field field is required.";
            }
        }
        return $errors;
    }

    protected function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

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

    protected function isAuthenticated()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
    }

    public function showDebugInfo()
    {
        if (Env::get('APP_DEBUG', false)) {
            Debug::showDebugPage();
        } else {
            $this->show404();
        }
    }

    public function getMemoryUsage()
    {
        return Debug::getMemoryUsage();
    }

    public function getExecutionTime()
    {
        return Debug::getExecutionTime();
    }
}
?>
