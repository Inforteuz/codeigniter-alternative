<?php
/**
 * BaseController.php
 *
 * This file provides the enhanced base controller class with CodeIgniter 4 inspired features.
 * It serves as the foundation for all other controllers with extended functionality.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    2.2.0
 * @date       2025-01-12
 *
 * @description
 * Enhanced functionality includes:
 *
 * 1. **Request & Response Handling**
 * 2. **Model & Helper Loading**
 * 3. **Advanced Validation**
 * 4. **Cookie & Session Management**
 * 5. **URL & Routing Helpers**
 * 6. **Security Features**
 * 7. **Enhanced View System**
 *    - renderPartial() - Render without layout
 *    - insert() - Simple include
 *    - asset() - Static files URL
 *    - csrfField() - CSRF input field
 *    - old() - Form values
 *    - showErrorBlock() - Validation errors
 *    - renderComponent() - View components
 */
namespace System;
use System\Core\Env;
use System\Database\Database;
use System\Core\Debug;
use System\Core\DebugToolbar;
use System\Cache\Cache;
use System\Cache\CacheHelper;

class BaseController
{
    protected $db;
    protected $base_url;
    protected $models = [];
    protected $helpers = [];
    protected $request = [];
    protected $validationErrors = [];

    // View system properties
    protected $layout = 'default';
    protected $useLayout = true;
    protected $viewData = [];
    protected $sections = [];
    protected $currentSection = null;
    protected $viewRendered = false;
    
    // Asset stacks for scripts and styles
    protected $stacks = [];
    protected $currentStack = null;
    
    // Slot system for components
    protected $slots = [];
    protected $currentSlot = null;
    
    // Old input data for form repopulation
    protected $oldInput = [];

    protected $viewComposers = [];

    public function __construct()
    {
        Env::load();

        $this->initializeSession();
        $this->db = Database::getInstance();
        $this->base_url = $this->getBaseUrl();
        $this->initializeRequest();
        $this->initializeCSRF();
        $this->loadOldInput();
        $this->registerViewComposers();
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
    * Starts a session with custom settings.
    * Saves files to the writable/session folder.
    */
    private function initializeSession()
    {

        if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
        }

        $sessionPath = __DIR__ . '/../writable/session';

        if (!is_dir($sessionPath)) {
            if (!mkdir($sessionPath, 0777, true) && !is_dir($sessionPath)) {
                $this->logError('Session: Failed to create session save path: ' . $sessionPath);
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                return;
            }
        }

        session_save_path($sessionPath);
        $cookieName = Env::get('SESSION_NAME', 'ci4_session');
        $cookieLifetime = (int)Env::get('SESSION_LIFETIME', 7200);
        $cookiePath = Env::get('SESSION_PATH', '/');
        $cookieDomain = Env::get('SESSION_DOMAIN', '');
        $cookieSecure = (bool)Env::get('SESSION_SECURE', isset($_SERVER['HTTPS']));
        $cookieHttpOnly = (bool)Env::get('SESSION_HTTPONLY', true);
        $cookieSameSite = Env::get('SESSION_SAMESITE', 'Lax');

        session_name($cookieName);
        session_set_cookie_params([
            'lifetime' => $cookieLifetime,
            'path' => $cookiePath,
            'domain' => $cookieDomain,
            'secure' => $cookieSecure,
            'httponly' => $cookieHttpOnly,
            'samesite' => $cookieSameSite
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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

    /**
     * Load old input data from session
     */
    private function loadOldInput()
    {
        if (isset($_SESSION['_old_input'])) {
            $this->oldInput = $_SESSION['_old_input'];
            unset($_SESSION['_old_input']);
        }
    }

    /**
     * Store current input for next request
     */
    protected function flashInput()
    {
        $_SESSION['_old_input'] = $_POST;
    }

    /**
    * Sets a flash message with a redirect.
    *
    * @param string|array $key Message key or key-value array
    * @param mixed $message Message text if $key is a string
    * @return self
    */
    public function with($key, $message = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setFlashMessage($k, $v);
            }
        } else {
            $this->setFlashMessage($key, $message);
        }
        
        return $this;
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
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

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
    * @param string $filePath Full path to the file on the server
    * @param string|null $clientName The name of the file that will be visible to the user
    * @return never
    */
    public function respondWithDownload($filePath, $clientName = null)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->logError("Download error: File not found or not readable at {$filePath}");
            return $this->showError(404, 'File not found');
        }

        $fileName = $clientName ?? basename($filePath);

        if (ob_get_level()) {
            ob_end_clean();
        }

        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($fileName) . '"');
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->setHeader('Content-Length', filesize($filePath));
        $this->setHeader('Expires', '0');
        $this->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $this->setHeader('Pragma', 'public');

        readfile($filePath);
        exit;
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

    /**
     * Validate data using BaseModel validation
     * @param \System\BaseModel $model
     * @param array $data
     * @return bool
     */
    public function validateWithModel(\System\BaseModel $model, array $data)
    {
        if (!$model->validate($data)) {
            $this->validationErrors = $model->getErrors();
            $this->flashInput();
            return false;
        }
        $this->validationErrors = [];
        return true;
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

    // ===== VIEW SYSTEM (CI4-INSPIRED) =====

    /**
     * Register view composers
     */
    protected function registerViewComposers()
    {
    
       $this->viewComposers['layouts/default'] = '\App\Composers\GlobalComposer@composeLayout';
    // $this->viewComposers['partials/header'] = '\App\Composers\GlobalComposer@composeHeader';
    // $this->viewComposers['admin/dashboard'] = '\App\Composers\GlobalComposer@composeDashboard';

    }

    /**
     * Run view composers for a specific view
     */
    protected function runViewComposers($view)
    {
        $composersToRun = [];
        if (isset($this->viewComposers['*'])) {
            $composersToRun[] = $this->viewComposers['*'];
        }
        if ($this->layout && isset($this->viewComposers[$this->layout])) {
            $composersToRun[] = $this->viewComposers[$this->layout];
        }
        if (isset($this->viewComposers[$view])) {
            $composersToRun[] = $this->viewComposers[$view];
        }

        foreach (array_unique($composersToRun) as $composer) {
            if (is_callable($composer)) {
                $composer($this);
            } elseif (is_string($composer) && strpos($composer, '@') !== false) {
                list($class, $method) = explode('@', $composer);
                if (class_exists($class)) { 
                    $composerInstance = new $class();
                    if (method_exists($composerInstance, $method)) {
                        $composerInstance->$method($this);
                    }
                }
            }
        }
    }

    /**
     * Main view rendering method with caching and composers
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to view
     * @return void
     */
    protected function view($view, $data = [])
    {
        if ($this->viewRendered) {
            return;
        }
        $hasFlash = false;
        if (isset($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                if (stripos($key, 'flash') !== false && !empty($value)) {
                    $hasFlash = true;
                    break;
                }
            }
        }
        $cacheKey = 'view_' . md5($view . serialize($data));
        $cachedView = null;
        
        if ($this->isViewCachingEnabled() && !$this->isDebug() && !$hasFlash) {
            $cachedView = $this->cache($cacheKey, null, 1);
        }
        
        if ($cachedView !== null) {
            echo $cachedView;
            $this->viewRendered = true;
            return;
        }
        try {
            $this->runViewComposers($view);
            $this->sections = [];
            $this->currentSection = null;
            $this->viewData = array_merge($this->viewData, $data);
            if ($this->shouldExcludeFromLayout($view)) {
                $this->useLayout = false;
            }
            $viewFile = $this->getViewPath($view);
            if (!file_exists($viewFile)) {
                throw new \Exception("View file \"{$view}.php\" not found at {$viewFile}");
            }
            if ($this->isDebug()) {
                echo "\n\n";
            }
            ob_start();
            extract($this->viewData, EXTR_SKIP);
            require $viewFile;
            $rogueContent = ob_get_clean();
            if ($this->useLayout && $this->layout) {
                if (!isset($this->sections['content'])) {
                    $this->sections['content'] = $rogueContent;
                }
                $output = $this->renderWithLayout($this->layout);
            } else {
                $output = $rogueContent;
            }
            if ($this->isDebug()) {
                $output .= "\n\n";
            }

            if ($this->isHtmlMinifyEnabled() && !$this->isDebug()) {
                $output = $this->minifyHtml($output);
            }

            if ($this->isViewCachingEnabled() && !$this->isDebug() && !$hasFlash) {
                $this->cache($cacheKey, $output, 3600);
            } else {
                $this->cache($cacheKey, null, 0);
            }
            
            echo $output;
            $this->viewRendered = true;
        } catch (\Throwable $e) {
            $this->handleViewError($e);
        }
    }

    /**
     * Render view without layout (for AJAX, modals, partials)
     * @param string $view View file path
     * @param array $data Data to pass to view
     * @param bool $return Whether to return content instead of echoing
     * @return string|void
     */
    protected function renderPartial($view, $data = [], $return = false)
    {
        $originalUseLayout = $this->useLayout;
        $this->useLayout = false;
        
        $partialData = array_merge($this->viewData, $data);
        extract($partialData);

        $viewFile = $this->getViewPath($view);

        if (!file_exists($viewFile)) {
            $error = "Partial view \"{$view}.php\" not found";
            $output = "";
            
            if ($return) {
                return $output;
            }
            echo $output;
            return;
        }

        ob_start();

        if ($this->isDebug()) {
            echo "\n\n";
        }

        require $viewFile;

        if ($this->isDebug()) {
            echo "\n\n";
        }

        $this->useLayout = $originalUseLayout;

        $output = ob_get_clean();

        if (!$this->isDebug()) {
            $output = $this->minifyHtml($output);
        }

        if ($return) {
            return $output;
        }
        
        echo $output; 
    }

    /**
     * Simplified include method (alias for includeView)
     * @param string $view View path
     * @param array $data Additional data
     * @return void
     */
    protected function insert($view, $data = [])
    {
        $this->includeView($view, $data, false);
    }

    /**
     * Generate asset URL for CSS, JS, images
     * @param string $path Path to asset (relative to public folder)
     * @param string $type Asset type (css, js, img)
     * @return string
     */
    protected function asset($path, $type = '')
    {
        $path = ltrim($path, '/');
        
        if ($type) {
            $typePath = rtrim($type, '/');
            return $this->base_url("assets/{$typePath}/{$path}");
        }
        
        return $this->base_url("assets/{$path}");
    }

    /**
     * Generate CSRF input field for forms
     * @param bool $return Whether to return HTML instead of echoing
     * @return string|void
     */
    protected function csrfField($return = false)
    {
        $token = $this->getCSRFToken();
        $html = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        
        if ($return) {
            return $html;
        }
        
        echo $html;
    }

    /**
     * Get old form input value (for form repopulation)
     * @param string $key Input name
     * @param mixed $default Default value
     * @return mixed
     */
    protected function old($key, $default = '')
    {
        return $this->oldInput[$key] ?? $default;
    }

    /**
     * Display validation error block for a field
     * @param string $field Field name
     * @param string $class CSS class for error container
     * @param bool $return Whether to return HTML instead of echoing
     * @return string|void
     */
    protected function showErrorBlock($field, $class = 'error-message', $return = false)
    {
        $errors = $this->getValidationErrors($field);
        
        if (empty($errors)) {
            return $return ? '' : null;
        }

        $html = '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($errors as $error) {
            $html .= '<p>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $html .= '</div>';

        if ($return) {
            return $html;
        }

        echo $html;
    }

    /**
     * Render small reusable view component
     * @param string $component Component name (stored in Views/components/)
     * @param array $data Data to pass to component
     * @param bool $return Whether to return content
     * @return string|void
     */
    protected function renderComponent($component, $data = [], $return = false)
    {
        $component = ltrim($component, '/');
        if (!preg_match('/^[a-z0-9\/_\-]+$/i', $component)) {
            $msg = "Invalid component name: {$component}";
            if ($return) return "<!-- ERROR: {$msg} -->";
            echo "<!-- ERROR: {$msg} -->";
            return;
        }

        $componentFile = realpath(__DIR__ . "/../app/Views/components/{$component}.php");
        $componentsDir = realpath(__DIR__ . "/../app/Views/components");

        if ($componentFile === false || strpos($componentFile, $componentsDir) !== 0 || !is_file($componentFile)) {
            $error = "Component \"{$component}.php\" not found";
            if ($return) {
                return "<!-- ERROR: {$error} -->";
            }
            echo "<!-- ERROR: {$error} -->";
            return;
        }

        $componentData = array_merge($this->viewData, $data);

        if ($return) ob_start();

        if ($this->isDebug()) {
            echo "\n<!-- DEBUG-COMPONENT-START\n";
            echo "     Name: {$component}\n";
            echo "     File: app/Views/components/{$component}.php\n";
            echo "     Props: " . (!empty($data) ? implode(', ', array_keys($data)) : 'none') . "\n";
            echo "-->\n";
        }

        (function($file, $vars) {
            extract($vars, EXTR_SKIP);
            require $file;
        })($componentFile, $componentData);

        if ($this->isDebug()) {
            echo "\n<!-- DEBUG-COMPONENT-END: {$component} -->\n";
        }

        if ($return) {
            return ob_get_clean();
        }
    }

    /**
     * Start a named stack (for scripts, styles, etc.)
     * @param string $name Stack name
     * @return void
     */
    protected function push($name)
    {
        if ($this->currentStack) {
            throw new \LogicException("Cannot nest stacks: already in stack '{$this->currentStack}'");
        }

        $this->currentStack = $name;
        ob_start();
    }

    /**
     * End current stack
     * @return void
     */
    protected function endPush()
    {
        if (!$this->currentStack) {
            throw new \LogicException("No stack started");
        }

        $content = ob_get_clean();
        $name = $this->currentStack;

        if (!isset($this->stacks[$name])) {
            $this->stacks[$name] = '';
        }

        $this->stacks[$name] .= $content;
        $this->currentStack = null;
    }

    /**
     * Render a stack
     * @param string $name Stack name
     * @return string
     */
    protected function stack($name)
    {
        $content = $this->stacks[$name] ?? '';
        
        if ($this->isDebug() && !empty($content)) {
            return "\n<!-- DEBUG-STACK: {$name} -->\n" . $content;
        }
        
        return $content;
    }

    /**
     * Start a slot (for component content injection)
     * @param string $name Slot name
     * @return void
     */
    protected function slot($name)
    {
        if ($this->currentSlot) {
            throw new \LogicException("Cannot nest slots: already in slot '{$this->currentSlot}'");
        }

        $this->currentSlot = $name;
        ob_start();
    }

    /**
     * End current slot
     * @return void
     */
    protected function endSlot()
    {
        if (!$this->currentSlot) {
            throw new \LogicException("No slot started");
        }

        $content = ob_get_clean();
        $this->slots[$this->currentSlot] = $content;
        $this->currentSlot = null;
    }

    /**
     * Get slot content
     * @param string $name Slot name
     * @param string $default Default content
     * @return string
     */
    protected function getSlot($name, $default = '')
    {
        return $this->slots[$name] ?? $default;
    }

    /**
     * Check if view should be excluded from layout rendering
     * @param string $view View path
     * @return bool
     */
    protected function shouldExcludeFromLayout($view)
    {
        $excludedViews = [
            'home/index',
            'welcome_message',
            'home/home',
            'welcome'
        ];
        
        return in_array($view, $excludedViews);
    }

    /**
     * Get full path to view file
     * @param string $view
     * @return string
     */
    protected function getViewPath($view)
    {
        return __DIR__ . "/../app/Views/{$view}.php";
    }

    /**
     * Render view with layout
     * @param string $layout Layout name
     * @return string
     */
    protected function renderWithLayout($layout)
    {
        $layoutFile = $this->getLayoutPath($layout);

        if (!file_exists($layoutFile)) {
            return $this->sections['content'] ?? '';
        }

        $this->runViewComposers($layout);

        extract($this->viewData, EXTR_SKIP);

        ob_start();
        
        if ($this->isDebug()) {
            echo "\n<!-- DEBUG-LAYOUT-START\n";
            echo "     File: app/Views/{$layout}.php\n";
            echo "     Sections: " . (!empty($this->sections) ? implode(', ', array_keys($this->sections)) : 'none') . "\n";
            echo "-->\n";
        }
        
        require $layoutFile;
        
        $output = ob_get_clean();
        
        if ($this->isDebug()) {
            $output .= "\n<!-- DEBUG-LAYOUT-END: app/Views/{$layout}.php -->\n";
        }
        
        return $output;
    }


    /**
     * Get full path to layout file (CI4-style)
     * @param string $layout Layout name (e.g. 'layouts/default' or 'default')
     * @return string
     */
    protected function getLayoutPath($layout)
    {

        $layout = preg_replace('/\.php$/', '', $layout);
        $layoutPath = __DIR__ . "/../app/Views/{$layout}.php";

        if (!file_exists($layoutPath)) {
            $layoutPath = __DIR__ . "/../app/Views/layouts/{$layout}.php";
        }

        return $layoutPath;
    }

    /**
     * Set which layout to use
     * @param string $layout Layout name (without .php)
     * @return self
     */
    protected function setLayout($layout)
    {
        $this->layout = $layout;
        $this->useLayout = true;
        return $this;
    }

    /**
     * Extend a layout (used in view files)
     * @param string $layout Layout name
     * @return self
     */
    protected function extend($layout)
    {
        $this->layout = $layout;
        $this->useLayout = true;
        return $this;
    }

    /**
     * Disable layout rendering
     * @return self
     */
    protected function noLayout()
    {
        $this->useLayout = false;
        return $this;
    }

    /**
     * Start a section (used in view files)
     * @param string $name Section name
     * @return void
     */
    protected function section($name)
    {
        if ($this->currentSection) {
            throw new \LogicException("Cannot nest sections: already in section '{$this->currentSection}'");
        }

        $this->currentSection = $name;
        ob_start();
        $skipDebugComment = in_array($name, ['title', 'meta', 'page_title']);
        
        if ($this->isDebug() && !$skipDebugComment) {
            echo "\n<!-- DEBUG-SECTION-START: {$name} -->\n";
        }
    }

    /**
     * End current section (used in view files)
     * @return void
     */
    protected function endSection()
    {
        if (!$this->currentSection) {
            throw new \LogicException("No section started");
        }

        $content = ob_get_clean();
        
        $skipDebugComment = in_array($this->currentSection, ['title', 'meta', 'page_title']);
        
        if ($this->isDebug() && !$skipDebugComment) {
            $content .= "\n<!-- DEBUG-SECTION-END: {$this->currentSection} -->\n";
        }

        $this->sections[$this->currentSection] = $content;
        $this->currentSection = null;
    }

    /**
     * Render a section (used in layout files)
     * @param string $name Section name
     * @param bool $escape Whether to escape HTML
     * @return string
     */
    protected function renderSection($name, $escape = false)
    {
        $content = $this->sections[$name] ?? '';
        
        $skipDebugComment = in_array($name, ['title', 'meta', 'page_title']);
        
        if ($this->isDebug() && !$skipDebugComment) {
            if ($content === '') {
                return "\n<!-- DEBUG-SECTION-RENDER: {$name} (EMPTY) -->\n";
            }
            
            $prefix = "\n<!-- DEBUG-SECTION-RENDER-START: {$name} -->\n";
            $suffix = "\n<!-- DEBUG-SECTION-RENDER-END: {$name} -->\n";
            
            return $prefix . ($escape ? htmlspecialchars($content, ENT_QUOTES, 'UTF-8') : $content) . $suffix;
        }
        
        return $escape ? htmlspecialchars($content, ENT_QUOTES, 'UTF-8') : $content;
    }

    /**
     * Check if section exists
     * @param string $name Section name
     * @return bool
     */
    protected function hasSection($name)
    {
        return isset($this->sections[$name]);
    }

    /**
     * Include a partial view
     * @param string $view View path
     * @param array $data Additional data
     * @param bool $return Whether to return content
     * @return string|void
     */
    protected function includeView($view, $data = [], $return = false)
    {
        $partialData = array_merge($this->viewData, $data);
        extract($partialData);

        $viewFile = $this->getViewPath($view);

        if ($return) ob_start();

        if (is_file($viewFile)) {
            if ($this->isDebug()) {
                echo "\n<!-- DEBUG-INCLUDE-START: app/Views/{$view}.php -->\n";
            }
            
            require $viewFile;
            
            if ($this->isDebug()) {
                echo "\n<!-- DEBUG-INCLUDE-END: app/Views/{$view}.php -->\n";
            }
        } else {
            if ($this->isDebug()) {
                echo "\n<!-- DEBUG-INCLUDE-NOT-FOUND: app/Views/{$view}.php -->\n";
            }
        }

        if ($return) {
            return ob_get_clean();
        }
    }

    /**
     * Set view data
     * @param string|array $key
     * @param mixed $value
     * @return self
     */
    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }
        return $this;
    }

    /**
     * Get view data
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getData($key = null, $default = null)
    {
        if ($key === null) {
            return $this->viewData;
        }
        return $this->viewData[$key] ?? $default;
    }

    /**
     * Append content to section
     * @param string $name Section name
     * @param string $content Content to append
     * @return void
     */
    protected function appendSection($name, $content)
    {
        if (isset($this->sections[$name])) {
            $this->sections[$name] .= $content;
        } else {
            $this->sections[$name] = $content;
        }
    }

    /**
     * Prepend content to section
     * @param string $name Section name
     * @param string $content Content to prepend
     * @return void
     */
    protected function prependSection($name, $content)
    {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = $content . $this->sections[$name];
        } else {
            $this->sections[$name] = $content;
        }
    }

    /**
     * Escape HTML string
     * @param string $string
     * @return string
     */
    protected function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Alias for escape
     * @param string $string
     * @return string
     */
    protected function esc($string)
    {
        return $this->escape($string);
    }

    /**
     * Handle view errors
     * @param \Throwable $e
     * @return void
     */
    protected function handleViewError(\Throwable $e)
    {
        $this->logError("View error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

        if ($this->isDebug()) {
            $this->showError(500, $e->getMessage());
        } else {
            $this->showError(500, 'An error occurred while rendering the view');
        }
    }

    /**
     * Check if debug mode is enabled
     * @return bool
     */
    protected function isDebug()
    {
        $debug = \System\Core\Env::get('DEBUG_MODE');
        return in_array(strtolower((string)$debug), ['1', 'true', 'on'], true);
    }

    /**
     * Minify HTML output to a single line.
     * @param string $buffer The HTML content
     * @return string Minified HTML
     */
    
    protected function minifyHtml($buffer)
    {
        if (trim($buffer) === '') {
            return $buffer;
        }

        preg_match_all('#<(script|style|pre|textarea)\b[^>]*>.*?</\1>#is', $buffer, $matches);

        $placeholders = [];
        foreach ($matches[0] as $i => $match) {
            $placeholder = "@@HTMLMINIFIER_PLACEHOLDER_{$i}@@";
            $placeholders[$placeholder] = $match;
            $buffer = str_replace($match, $placeholder, $buffer);
        }

        $search = [
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s',
        ];
        $replace = [
            '>',
            '<',
            '\\1',
        ];

        $buffer = preg_replace($search, $replace, $buffer);
        $buffer = str_replace(["\r\n", "\r", "\n", "\t"], '', $buffer);
        $buffer = trim($buffer);

        foreach ($placeholders as $placeholder => $original) {
            $buffer = str_replace($placeholder, $original, $buffer);
        }

        return $buffer;
    }

    /**
     * Alias for view() with settings support
     * @param string $viewPath
     * @param array $data
     * @return void
     */
    protected function renderView($viewPath, $data = [])
    {
        if (property_exists($this, 'settings') && is_array($this->settings)) {
            $data['settings'] = $this->settings;
        }

        $this->view($viewPath, $data);
    }

    // ===== ORIGINAL METHODS =====

    public function to($url)
    {
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = $this->base_url($url);
        }
        header("Location: " . $url);
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
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0] ?? [];
        $file = $backtrace['file'] ?? 'unknown';
        $line = $backtrace['line'] ?? 'unknown';

        echo "<div style='
            background:#1e1e1e;
            color:#dcdcdc;
            font-family:Consolas,monospace;
            font-size:13px;
            padding:20px;
            margin:15px;
            border-left:5px solid #007acc;
            border-radius:6px;
            line-height:1.5;
        '>";

        echo "<div style='color:#9cdcfe;margin-bottom:8px;'>
                <strong>Debug dump:</strong> <small>{$file}:{$line}</small>
              </div>";
        echo "<pre style='white-space:pre-wrap;margin:0;color:#ce9178;'>";
        print_r($data);
        echo "</pre>";

        echo "</div>";

        if (method_exists($this, 'logDebug')) {
            $this->logDebug("DD at {$file}:{$line}\n" . print_r($data, true));
        }

        if ($stop) exit;
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

    public function cache($key, $value = null, $duration = 3600)
    {
        if ($value === null) {
            $cached = Cache::get($key);
            if ($cached !== null) {
                \System\Core\DebugToolbar::log("Cache hit: {$key}", 'cache');
            } else {
                \System\Core\DebugToolbar::log("Cache miss: {$key}", 'cache');
            }
            return $cached;
        }

        Cache::put($key, $value, $duration);
        \System\Core\DebugToolbar::log("Cache stored: {$key}", 'cache');
        return true;
    }

    public function cacheRemember($key, $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public function cacheWithTags($tags, $key, $ttl, callable $callback)
    {
        return CacheHelper::cacheTags()->tag($tags)->remember($key, $ttl, $callback);
    }

    public function cacheForget($key)
    {
        \System\Core\DebugToolbar::log("Cache forget: {$key}", 'cache');
        return Cache::forget($key);
    }

    public function cacheFlush()
    {
        \System\Core\DebugToolbar::log("Cache flushed manually", 'cache');
        return Cache::flush();
    }

    public function cacheFlushTag($tag)
    {
        $deleted = CacheHelper::flushTag($tag);
        \System\Core\DebugToolbar::log("Cache tag flushed: {$tag} ({$deleted} files)", 'cache');
        return $deleted;
    }

    public function cacheStats()
    {
        return Cache::getStats();
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

    protected function isViewCachingEnabled()
    {
        return filter_var(Env::get('VIEW_CACHING', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    protected function isHtmlMinifyEnabled()
    {
        return filter_var(Env::get('MINIFY_HTML', 'false'), FILTER_VALIDATE_BOOLEAN);
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
