# PHP MVC Framework - Mukammal Qo'llanma

## FAYL STRUKTURASI
\`\`\`
project/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── AdminController.php
│   │   ├── AuthController.php
│   │   └── ApiController.php
│   ├── Models/
│   │   ├── LoginModel.php
│   │   ├── UserModel.php
│   │   └── BaseModel.php
│   ├── Middlewares/
│   │   ├── AuthMiddleware.php
│   │   ├── AdminMiddleware.php
│   │   └── CorsMiddleware.php
│   └── Views/
│       ├── layouts/
│       │   ├── header.php
│       │   └── footer.php
│       ├── home/
│       │   └── index.php
│       └── admin/
│           └── dashboard.php
├── system/
│   ├── BaseController.php
│   ├── BaseModel.php
│   ├── Router.php
│   ├── Core/
│   │   ├── Env.php
│   │   └── Middleware.php
│   └── Database/
│       └── Database.php
├── scripts/
│   ├── create_users_table.sql
│   └── seed_admin_user.sql
├── public/
│   ├── css/
│   ├── js/
│   └── images/
├── .env
├── index.php
└── autoloader.php
\`\`\`

## 1. ENVIRONMENT SETUP (.env fayli)

### .env Fayl Yaratish
\`\`\`env
# Database Configuration
DB_HOST=localhost
DB_NAME=crm_database
DB_USER=root
DB_PASS=password
DB_PORT=3306

# Application Settings
APP_NAME="CRM System"
APP_URL=http://crm.uz
APP_DEBUG=true

# Session Settings
SESSION_LIFETIME=7200
SESSION_NAME=crm_session

# Security
APP_KEY=your-secret-key-here
HASH_ALGO=PASSWORD_DEFAULT
\`\`\`

### Environment Variables Ishlatish
\`\`\`php
// Controller yoki Model da
$appName = $_ENV['APP_NAME'];
$debug = $_ENV['APP_DEBUG'] === 'true';
\`\`\`

## 2. DATABASE MIGRATIONS

### Migration Yaratish
\`\`\`sql
-- scripts/001_create_users_table.sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index qo'shish
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_remember_token ON users(remember_token);
\`\`\`

### Seeding (Test Ma'lumotlar)
\`\`\`sql
-- scripts/002_seed_admin_user.sql
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@crm.uz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('user1', 'user1@crm.uz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');
\`\`\`

## 3. AUTHENTICATION SISTEMA

### AuthController
\`\`\`php
// app/Controllers/AuthController.php
<?php
namespace App\Controllers;
use System\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    public function login()
    {
        if ($_POST) {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            $user = $this->userModel->authenticate($username, $password);
            
            if ($user) {
                session_start();
                $_SESSION['user'] = $user;
                
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $this->userModel->setRememberToken($user['id'], $token);
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                }
                
                $this->redirect('/dashboard');
            } else {
                $this->view('auth/login', ['error' => 'Noto\'g\'ri login yoki parol']);
            }
        }
        
        $this->view('auth/login');
    }
    
    public function logout()
    {
        session_start();
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
        $this->redirect('/login');
    }
}
\`\`\`

### UserModel
\`\`\`php
// app/Models/UserModel.php
<?php
namespace App\Models;
use System\BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    
    public function authenticate($username, $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    public function setRememberToken($userId, $token)
    {
        $hashedToken = hash('sha256', $token);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET remember_token = ? WHERE id = ?");
        return $stmt->execute([$hashedToken, $userId]);
    }
    
    public function getUserByRememberToken($token)
    {
        $hashedToken = hash('sha256', $token);
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE remember_token = ?");
        $stmt->execute([$hashedToken]);
        return $stmt->fetch();
    }
    
    public function createUser($data)
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (username, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'user'
        ]);
    }
}
\`\`\`

## 4. ADVANCED MIDDLEWARE

### CORS Middleware
\`\`\`php
// app/Middlewares/CorsMiddleware.php
<?php
namespace App\Middlewares;

class CorsMiddleware
{
    public function handle()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        return true;
    }
}
\`\`\`

### Rate Limiting Middleware
\`\`\`php
// app/Middlewares/RateLimitMiddleware.php
<?php
namespace App\Middlewares;

class RateLimitMiddleware
{
    private $maxRequests = 100;
    private $timeWindow = 3600; // 1 soat
    
    public function handle()
    {
        session_start();
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = "rate_limit_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
        }
        
        $data = $_SESSION[$key];
        
        if (time() - $data['start_time'] > $this->timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
            return true;
        }
        
        if ($data['count'] >= $this->maxRequests) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
    
    public function redirectTo()
    {
        http_response_code(429);
        return '/error/too-many-requests';
    }
}
\`\`\`

## 5. API DEVELOPMENT

### API Controller
\`\`\`php
// app/Controllers/ApiController.php
<?php
namespace App\Controllers;
use System\BaseController;
use App\Models\UserModel;

class ApiController extends BaseController
{
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        header('Content-Type: application/json');
    }
    
    public function users()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $users = $this->userModel->getAllUsers();
                $this->jsonResponse(['success' => true, 'data' => $users]);
                break;
                
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
                
                if ($this->validateUserData($input)) {
                    $result = $this->userModel->createUser($input);
                    $this->jsonResponse(['success' => $result, 'message' => 'User created']);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid data'], 400);
                }
                break;
                
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
    }
    
    private function validateUserData($data)
    {
        return isset($data['username']) && 
               isset($data['email']) && 
               isset($data['password']) &&
               filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    }
    
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}
\`\`\`

### API Route'lar
\`\`\`php
// system/Router.php da
$this->addRoute('GET', 'api/users', 'ApiController', 'users', ['CorsMiddleware', 'AuthMiddleware']);
$this->addRoute('POST', 'api/users', 'ApiController', 'users', ['CorsMiddleware', 'AuthMiddleware']);
$this->addRoute('PUT', 'api/users/{id}', 'ApiController', 'updateUser', ['CorsMiddleware', 'AuthMiddleware']);
\`\`\`

## 6. FORM VALIDATION

### Validation Helper
\`\`\`php
// system/Validator.php
<?php
namespace System;

class Validator
{
    private $errors = [];
    
    public function validate($data, $rules)
    {
        foreach ($rules as $field => $rule) {
            $this->validateField($field, $data[$field] ?? null, $rule);
        }
        
        return empty($this->errors);
    }
    
    private function validateField($field, $value, $rules)
    {
        $ruleArray = explode('|', $rules);
        
        foreach ($ruleArray as $rule) {
            if ($rule === 'required' && empty($value)) {
                $this->errors[$field][] = "{$field} maydoni to'ldirilishi shart";
            }
            
            if (strpos($rule, 'min:') === 0 && strlen($value) < substr($rule, 4)) {
                $this->errors[$field][] = "{$field} kamida " . substr($rule, 4) . " ta belgi bo'lishi kerak";
            }
            
            if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "{$field} to'g'ri email formatida bo'lishi kerak";
            }
        }
    }
    
    public function getErrors()
    {
        return $this->errors;
    }
}
\`\`\`

### Controller da Validation
\`\`\`php
// Controller da
use System\Validator;

public function store()
{
    $validator = new Validator();
    $rules = [
        'username' => 'required|min:3',
        'email' => 'required|email',
        'password' => 'required|min:6'
    ];
    
    if ($validator->validate($_POST, $rules)) {
        // Ma'lumotlar to'g'ri
        $this->userModel->createUser($_POST);
        $this->redirect('/users');
    } else {
        // Xatoliklar bor
        $this->view('users/create', ['errors' => $validator->getErrors()]);
    }
}
\`\`\`

## 7. ERROR HANDLING VA DEBUGGING

### Global Error Handler
\`\`\`php
// system/ErrorHandler.php
<?php
namespace System;

class ErrorHandler
{
    public static function register()
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error = [
            'type' => 'Error',
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'time' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        
        if ($_ENV['APP_DEBUG'] === 'true') {
            self::displayError($error);
        } else {
            self::displayGenericError();
        }
    }
    
    private static function logError($error)
    {
        $logMessage = "[{$error['time']}] {$error['type']}: {$error['message']} in {$error['file']}:{$error['line']}\n";
        file_put_contents('logs/error.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    private static function displayError($error)
    {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>{$error['type']}</h3>";
        echo "<p><strong>Message:</strong> {$error['message']}</p>";
        echo "<p><strong>File:</strong> {$error['file']}:{$error['line']}</p>";
        echo "</div>";
    }
}
\`\`\`

## 8. COMMON ISSUES VA TROUBLESHOOTING

### 1. "Class not found" Xatoligi
**Sabab:** Autoloader namespace'ni topa olmayapti
**Yechim:**
\`\`\`php
// Namespace to'g'ri yozilganini tekshiring
namespace App\Controllers; // To'g'ri
namespace app\controllers; // Noto'g'ri

// Fayl nomi sinf nomi bilan mos kelishini tekshiring
class UserController // Fayl: UserController.php
\`\`\`

### 2. Database Connection Xatoligi
**Sabab:** .env fayli noto'g'ri yoki mavjud emas
**Yechim:**
\`\`\`php
// .env faylini tekshiring
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password

// Database.php da debug qo'shing
try {
    $this->connection = new PDO($dsn, $username, $password, $options);
    echo "Database connected successfully!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
\`\`\`

### 3. Route Ishlamayapti
**Sabab:** Route qo'shilmagan yoki noto'g'ri tartibda
**Yechim:**
\`\`\`php
// Aniq route'lar birinchi bo'lishi kerak
$this->addRoute('GET', 'users/create', 'UserController', 'create');
$this->addRoute('GET', 'users/{id}', 'UserController', 'show');

// .htaccess faylini tekshiring
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
\`\`\`

### 4. Session Ishlamayapti
**Sabab:** session_start() chaqirilmagan
**Yechim:**
\`\`\`php
// Har bir sahifada session_start() qo'shing
session_start();

// Yoki BaseController da global qiling
public function __construct()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
\`\`\`

## 9. BEST PRACTICES

### 1. Security
\`\`\`php
// SQL Injection'dan himoya
$stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);

// XSS'dan himoya
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// CSRF Token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
\`\`\`

### 2. Performance
\`\`\`php
// Database connection pooling
class Database {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Query optimization
// Yomon
$users = $this->db->query("SELECT * FROM users")->fetchAll();
foreach ($users as $user) {
    $posts = $this->db->query("SELECT * FROM posts WHERE user_id = {$user['id']}")->fetchAll();
}

// Yaxshi
$result = $this->db->query("
    SELECT u.*, p.title, p.content 
    FROM users u 
    LEFT JOIN posts p ON u.id = p.user_id
")->fetchAll();
\`\`\`

### 3. Code Organization
\`\`\`php
// Service Layer ishlatish
// app/Services/UserService.php
class UserService {
    private $userModel;
    
    public function createUser($data) {
        // Validation
        // Business logic
        // Database operations
        return $this->userModel->create($data);
    }
}

// Controller da
public function store() {
    $userService = new UserService();
    $result = $userService->createUser($_POST);
    
    if ($result) {
        $this->redirect('/users');
    }
}
\`\`\`

## 10. DEPLOYMENT

### Production Settings
\`\`\`env
# .env (Production)
APP_DEBUG=false
DB_HOST=production_host
DB_NAME=production_db
DB_USER=production_user
DB_PASS=strong_password

# Security headers
SECURE_HEADERS=true
HTTPS_ONLY=true
\`\`\`

### .htaccess (Apache)
```apache
RewriteEngine On

# HTTPS redirect
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
