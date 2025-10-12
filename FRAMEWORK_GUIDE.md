# CodeIgniter Alternative Framework - Official Guide

**Version:** 2.0.0
**Author:** Oyatillo
**PHP Requirement:** 8.1.9+
**License:** MIT

---

## Table of Contents

1. [Introduction](#introduction)
2. [Installation & Setup](#installation--setup)
3. [Architecture Overview](#architecture-overview)
4. [Routing System](#routing-system)
5. [Controllers](#controllers)
6. [Models](#models)
7. [Views](#views)
8. [Database Management](#database-management)
9. [Middleware System](#middleware-system)
10. [Caching](#caching)
11. [Security Features](#security-features)
12. [Debug & Error Handling](#debug--error-handling)
13. [Best Practices](#best-practices)
14. [API Reference](#api-reference)

---

## Introduction

**CodeIgniter Alternative** is a lightweight, fast, and modern PHP MVC framework designed as an alternative to CodeIgniter. It combines the simplicity of CodeIgniter with modern PHP features and enhanced functionality.

### Key Features

- **MVC Architecture** - Clean separation of concerns
- **Advanced Router** - Support for GET, POST, PUT, DELETE, PATCH with dynamic parameters
- **Query Builder** - Laravel-inspired database query builder
- **Middleware System** - Request filtering and authentication
- **Built-in Caching** - File and array-based caching with tag support
- **Debug Toolbar** - Comprehensive debugging with query tracking
- **CSRF Protection** - Built-in security against CSRF attacks
- **Migration System** - Database version control
- **Environment Configuration** - .env file support
- **Session Management** - Secure session handling
- **Error Handling** - Professional error pages for production

---

## Installation & Setup

### System Requirements

```
PHP >= 8.1.9
MySQL >= 5.7
Apache/Nginx with mod_rewrite
```

### Installation Steps

1. **Clone or Download** the framework
2. **Configure .env file**:

```env
# Application Configuration
APP_NAME="CodeIgniter Alternative"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost
TIMEZONE=Asia/Tashkent

# Database Configuration
DB_HOST=localhost
DB_NAME=your_database
DB_USER=root
DB_PASS=your_password
DB_CHARSET=utf8mb4

# Cache Configuration
CACHE_DRIVER=file

# Debug Configuration
DEBUG_MODE=true
```

3. **Set Permissions**:

```bash
chmod -R 755 writable/
chmod -R 755 writable/cache/
chmod -R 755 writable/logs/
chmod -R 755 writable/session/
```

4. **Configure Web Server**:

**Apache (.htaccess)**:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx**:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## Architecture Overview

### Directory Structure

```
project/
├── app/
│   ├── Controllers/       # HTTP request handlers
│   ├── Models/           # Database interaction layer
│   ├── Views/            # Presentation layer
│   ├── Middlewares/      # Request filters
│   ├── Routes/           # Route definitions
│   └── Database/
│       ├── Migrations/   # Database migrations
│       └── Seeds/        # Data seeders
│
├── system/
│   ├── BaseController.php    # Base controller class
│   ├── BaseModel.php         # Base model class
│   ├── Router.php            # Routing engine
│   ├── ErrorHandler.php      # Error management
│   ├── Redirect.php          # Redirect utilities
│   ├── Core/
│   │   ├── Auth.php          # Authentication
│   │   ├── Debug.php         # Debug utilities
│   │   ├── DebugToolbar.php  # Debug toolbar
│   │   ├── Env.php           # Environment loader
│   │   └── Middleware.php    # Middleware manager
│   ├── Cache/
│   │   ├── Cache.php         # Cache manager
│   │   ├── FileCache.php     # File cache driver
│   │   ├── ArrayCache.php    # Array cache driver
│   │   └── CacheHelper.php   # Cache utilities
│   ├── Database/
│   │   └── Database.php      # Database connection
│   └── Security/
│       └── Csrf.php          # CSRF protection
│
├── writable/
│   ├── cache/           # Cache files
│   ├── logs/            # Error & debug logs
│   ├── session/         # Session files
│   └── uploads/         # Uploaded files
│
├── index.php            # Entry point
├── autoloader.php       # Class autoloader
└── .env                 # Environment configuration
```

### Request Lifecycle

```
1. Browser Request
   ↓
2. index.php (Entry Point)
   ↓
3. autoloader.php (Load Classes)
   ↓
4. Router.php (Match Route)
   ↓
5. Middleware Execution
   ↓
6. Controller Method
   ↓
7. Model (Optional)
   ↓
8. View Rendering
   ↓
9. Response to Browser
```

---

## Routing System

### Basic Routing

Routes are defined in `app/Routes/Routes.php`:

```php
// GET route
$router->get('home', 'HomeController', 'index');

// POST route
$router->post('login', 'AuthController', 'login');

// PUT route
$router->put('user/{id}', 'UserController', 'update');

// DELETE route
$router->delete('user/{id}', 'UserController', 'delete');

// PATCH route
$router->patch('user/{id}', 'UserController', 'patch');
```

### Dynamic Parameters

```php
// Single parameter
$router->get('user/{id}', 'UserController', 'show');

// Multiple parameters
$router->get('post/{category}/{slug}', 'PostController', 'show');

// Controller method receives parameters
public function show($category, $slug)
{
    // $category and $slug are automatically passed
}
```

### Route Middleware

```php
// Single middleware
$router->get('dashboard', 'DashboardController', 'index', ['AuthMiddleware']);

// Multiple middlewares
$router->get('admin', 'AdminController', 'index', ['AuthMiddleware', 'AdminMiddleware']);
```

### Route Groups

```php
// Apply middleware to multiple routes
$router->group(['AuthMiddleware'], function($router) {
    $router->get('profile', 'ProfileController', 'index');
    $router->get('settings', 'SettingsController', 'index');
    $router->post('logout', 'AuthController', 'logout');
});

// Nested groups
$router->group(['AuthMiddleware'], function($router) {
    $router->group(['AdminMiddleware'], function($router) {
        $router->get('admin/users', 'AdminController', 'users');
        $router->get('admin/settings', 'AdminController', 'settings');
    });
});
```

### Available HTTP Methods

```php
$router->get($uri, $controller, $method, $middlewares);
$router->post($uri, $controller, $method, $middlewares);
$router->put($uri, $controller, $method, $middlewares);
$router->delete($uri, $controller, $method, $middlewares);
$router->patch($uri, $controller, $method, $middlewares);
```

---

## Controllers

### Creating Controllers

Controllers extend `BaseController` and are located in `app/Controllers/`:

```php
<?php

namespace App\Controllers;

use System\BaseController;

class UserController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->view('users/index', [
            'title' => 'User List'
        ]);
    }

    public function show($id)
    {
        $userModel = $this->model('UserModel');
        $user = $userModel->find($id);

        $this->view('users/show', [
            'user' => $user
        ]);
    }
}
```

### BaseController Methods

#### View Rendering

```php
// Load view with data
$this->view('view_name', ['key' => 'value']);

// Example
$this->view('home/index', [
    'title' => 'Welcome',
    'users' => $users
]);
```

#### Redirects

```php
// Redirect to URL
$this->to('/dashboard');

// Redirect with method chaining
$this->redirect()->to('/login');
```

#### Model Loading

```php
// Load model
$userModel = $this->model('UserModel');

// With alias
$userModel = $this->model('UserModel', 'users');
```

#### Request Data

```php
// Get POST data
$email = $this->getPost('email');
$password = $this->getPost('password');

// All POST data
$data = $this->getPost();

// GET data
$search = $this->getGet('search');

// Get from any method (POST, GET, PUT, etc.)
$value = $this->getVar('field_name');

// JSON input
$data = $this->getJSON();
```

#### Validation

```php
// Set validation rules
$this->setValidationRules([
    'email' => 'required|valid_email',
    'password' => 'required|min_length[6]',
    'username' => 'required|alpha_numeric'
]);

// Validate data
if ($this->validate($_POST)) {
    // Validation passed
} else {
    $errors = $this->getValidationErrors();
}
```

**Available Validation Rules:**
- `required` - Field must not be empty
- `valid_email` - Must be valid email
- `min_length[n]` - Minimum length
- `max_length[n]` - Maximum length
- `numeric` - Must be numeric
- `integer` - Must be integer
- `alpha` - Only letters
- `alpha_numeric` - Letters and numbers only

#### Response Methods

```php
// JSON response
$this->respondWithJSON(['status' => 'success'], 200);

// Success responses
$this->respondCreated(['id' => 123]);
$this->respondNoContent();

// Error responses
$this->respondBadRequest('Invalid input');
$this->respondUnauthorized('Login required');
$this->respondForbidden('Access denied');
$this->respondNotFound('Resource not found');
$this->respondInternalError('Server error');
```

#### File Uploads

```php
$result = $this->uploadFile(
    'fileInputName',
    ['jpg', 'png', 'gif'],  // allowed extensions
    10485760,                // max size (10MB)
    'products'               // folder name
);

if (isset($result['success'])) {
    $fileName = $result['fileName'];
    $filePath = $result['filePath'];
}
```

#### Flash Messages

```php
// Set flash message
$this->setFlash('success', 'User created successfully');
$this->setFlash('error', 'Something went wrong');

// Get flash message (in view)
$message = $this->getFlash('success');
```

#### Session Management

```php
// Set session
$this->setSession('key', 'value');
$this->setSession(['user_id' => 1, 'name' => 'John']);

// Get session
$userId = $this->getSession('user_id');

// Remove session
$this->unsetSession('key');
$this->unsetSession(['key1', 'key2']);
```

#### Security

```php
// CSRF token
$token = $this->generateCSRFToken();
$isValid = $this->verifyCSRFToken($token);

// XSS cleaning
$clean = $this->xssClean($userInput);

// Input sanitization
$email = $this->sanitizeInput($_POST['email'], 'email');
$age = $this->sanitizeInput($_POST['age'], 'int');
```

#### Debugging

```php
// Dump and die
$this->dd($variable);

// Dump without stopping
$this->dd($variable, false);
```

---

## Models

### Creating Models

Models extend `BaseModel` and are located in `app/Models/`:

```php
<?php

namespace App\Models;

use System\BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    // Enable timestamps
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Enable soft deletes
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
}
```

### Query Builder

#### SELECT Queries

```php
// Get all records
$users = $userModel->get();

// Get first record
$user = $userModel->first();

// Find by ID
$user = $userModel->find(1);

// Select specific fields
$users = $userModel->select('id, name, email')->get();

// Select distinct
$users = $userModel->distinct('email')->get();

// Aggregate functions
$maxAge = $userModel->selectMax('age')->first();
$minAge = $userModel->selectMin('age')->first();
$avgAge = $userModel->selectAvg('age')->first();
$totalUsers = $userModel->selectCount()->first();
```

#### WHERE Clauses

```php
// Simple where
$users = $userModel->where(['status' => 'active'])->get();

// Multiple conditions
$users = $userModel
    ->where(['status' => 'active', 'role' => 'admin'])
    ->get();

// OR where
$users = $userModel
    ->where(['status' => 'active'])
    ->orWhere(['status' => 'pending'])
    ->get();

// Comparison operators
$users = $userModel->where(['age >' => 18])->get();
$users = $userModel->where(['age <=' => 65])->get();
$users = $userModel->where(['status !=' => 'deleted'])->get();

// IN clause
$users = $userModel->whereIn('id', [1, 2, 3, 4])->get();

// NOT IN clause
$users = $userModel->whereNotIn('role', ['guest', 'banned'])->get();

// LIKE searches
$users = $userModel->like('name', 'John', 'both')->get();
$users = $userModel->like('email', '@gmail.com', 'before')->get();

// NOT LIKE
$users = $userModel->notLike('name', 'test')->get();

// BETWEEN
$users = $userModel->between('age', 18, 65)->get();

// NOT BETWEEN
$users = $userModel->notBetween('age', 0, 17)->get();
```

#### JOIN Operations

```php
// Inner join
$users = $userModel
    ->join('profiles', 'users.id = profiles.user_id')
    ->get();

// Left join
$users = $userModel
    ->leftJoin('profiles', 'users.id = profiles.user_id')
    ->get();

// Right join
$users = $userModel
    ->rightJoin('profiles', 'users.id = profiles.user_id')
    ->get();

// Multiple joins
$users = $userModel
    ->join('profiles', 'users.id = profiles.user_id')
    ->join('companies', 'users.company_id = companies.id')
    ->get();
```

#### GROUP BY & HAVING

```php
// Group by
$stats = $userModel
    ->select('role, COUNT(*) as count')
    ->groupBy('role')
    ->get();

// Having clause
$stats = $userModel
    ->select('role, COUNT(*) as count')
    ->groupBy('role')
    ->having('count > 10')
    ->get();
```

#### ORDER BY & LIMIT

```php
// Order by
$users = $userModel->orderBy('created_at', 'DESC')->get();

// Multiple order by
$users = $userModel
    ->orderBy('status', 'ASC')
    ->orderBy('created_at', 'DESC')
    ->get();

// Random order
$users = $userModel->orderByRandom()->get();

// Limit
$users = $userModel->limit(10)->get();

// Limit with offset
$users = $userModel->limit(10, 20)->get();
```

### INSERT Operations

```php
// Insert single record
$userId = $userModel->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT)
]);

// Insert batch (multiple records)
$inserted = $userModel->insertBatch([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com'],
    ['name' => 'User 3', 'email' => 'user3@example.com']
]);
```

### UPDATE Operations

```php
// Update record
$updated = $userModel->update('users',
    ['status' => 'active'],
    ['id' => 1]
);

// Update with query builder
$userModel
    ->where(['id' => 1])
    ->update('users', ['status' => 'active'], ['id' => 1]);

// Update batch
$updated = $userModel->updateBatch([
    ['id' => 1, 'status' => 'active'],
    ['id' => 2, 'status' => 'inactive'],
    ['id' => 3, 'status' => 'pending']
], 'id');
```

### DELETE Operations

```php
// Delete record
$userModel->delete('users', ['id' => 1]);

// Soft delete (if enabled)
$userModel->softDelete(1);

// Restore soft deleted
$userModel->restore(1);

// Include soft deleted in query
$users = $userModel->withDeleted()->get();

// Only soft deleted records
$users = $userModel->onlyDeleted()->get();
```

### Advanced Features

#### Pagination

```php
// Paginate results
$perPage = 20;
$currentPage = $this->getGet('page', 1);

$pagination = $this->paginate($totalRecords, $perPage, $currentPage, '/users');

// In view
foreach ($pagination['links'] as $link) {
    echo "<a href='{$link['url']}'>{$link['page']}</a>";
}
```

#### Search

```php
// Search across multiple fields
$users = $userModel
    ->search($searchTerm, ['name', 'email', 'phone'])
    ->get();
```

#### Filtering

```php
// Apply multiple filters
$users = $userModel
    ->filter([
        'status' => 'active',
        'role' => 'admin',
        'country' => 'USA'
    ])
    ->get();
```

#### Model Events

```php
// Add before insert callback
$userModel->addCallback('beforeInsert', function(&$data) {
    $data['created_by'] = $_SESSION['user_id'] ?? null;
});

// Add after insert callback
$userModel->addCallback('afterInsert', function(&$data) {
    // Send welcome email
});
```

---

## Views

Views are located in `app/Views/` and use PHP templates.

### Creating Views

**app/Views/users/index.php:**

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <h1><?= $title ?></h1>

    <table>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
```

### Loading Views

```php
// In controller
$this->view('users/index', [
    'title' => 'User List',
    'users' => $users
]);
```

### View Best Practices

1. **Always escape output** to prevent XSS:
```php
<?= htmlspecialchars($userInput) ?>
```

2. **Use short tags** for cleaner templates:
```php
<?= $variable ?>  // Instead of <?php echo $variable; ?>
```

3. **Organize views** in subdirectories:
```
app/Views/
├── users/
│   ├── index.php
│   ├── show.php
│   └── edit.php
├── layouts/
│   ├── header.php
│   └── footer.php
└── errors/
    ├── 404.php
    └── 500.php
```

---

## Database Management

### Database Configuration

Configure in `.env`:

```env
DB_HOST=localhost
DB_NAME=your_database
DB_USER=root
DB_PASS=your_password
DB_CHARSET=utf8mb4
```

### Migrations

Migrations are database version control, located in `app/Database/Migrations/`.

#### Creating Migrations

**app/Database/Migrations/2024-01-01-120000_create_users_table.php:**

```php
<?php

class CreateUsersTable
{
    public function up()
    {
        $db = \System\Database\Database::getInstance();

        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->execute($sql);
    }

    public function down()
    {
        $db = \System\Database\Database::getInstance();
        $db->execute("DROP TABLE IF EXISTS users");
    }
}
```

#### Running Migrations

Migrations automatically run on every request via `MigrateController`. To disable auto-migration, remove this from `index.php`:

```php
$migrateController = new \App\Controllers\MigrateController();
$migrateController->migrate();
```

### Raw Queries

```php
// Execute query
$db = \System\Database\Database::getInstance();
$result = $db->query("SELECT * FROM users WHERE status = ?", ['active']);

// Fetch single row
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [1]);

// Fetch all rows
$users = $db->fetchAll("SELECT * FROM users");
```

### Transactions

```php
$db->beginTransaction();

try {
    $db->insert('users', $userData);
    $db->insert('profiles', $profileData);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}
```

---

## Middleware System

Middleware filters HTTP requests. Located in `app/Middlewares/`.

### Creating Middleware

**app/Middlewares/AuthMiddleware.php:**

```php
<?php

namespace App\Middlewares;

use System\BaseController;

class AuthMiddleware extends BaseController
{
    public function handle()
    {
        // Check if user is logged in
        if (!$this->isAuthenticated()) {
            return false; // Block request
        }

        return true; // Allow request
    }

    public function redirectTo()
    {
        return '/login';
    }

    public function onFailure()
    {
        $this->setFlash('error', 'Please login first');
    }
}
```

### Applying Middleware

#### To Single Route

```php
$router->get('dashboard', 'DashboardController', 'index', ['AuthMiddleware']);
```

#### To Multiple Routes

```php
$router->group(['AuthMiddleware'], function($router) {
    $router->get('profile', 'ProfileController', 'index');
    $router->get('settings', 'SettingsController', 'index');
});
```

### Built-in Middleware

#### AuthMiddleware
Checks if user is authenticated.

#### GuestMiddleware
Allows only guests (non-authenticated users).

#### CsrfMiddleware
Validates CSRF tokens on POST requests.

#### RateLimitMiddleware
Limits requests per IP (100 per hour by default).

#### CorsMiddleware
Handles CORS headers for API requests.

#### LanguageMiddleware
Detects and sets application language.

#### MaintenanceMiddleware
Shows maintenance page when enabled in `.env`.

---

## Caching

The framework provides a powerful caching system with multiple drivers.

### Configuration

In `.env`:

```env
CACHE_DRIVER=file  # file, array, redis
```

### Basic Usage

```php
use System\Cache\Cache;

// Store data
Cache::put('key', 'value', 3600); // 1 hour

// Retrieve data
$value = Cache::get('key');

// Remember (get or store)
$users = Cache::remember('users', 3600, function() {
    return $userModel->all();
});

// Check existence
if (Cache::has('key')) {
    // Key exists
}

// Delete
Cache::forget('key');

// Clear all
Cache::flush();
```

### Controller Caching

```php
// In controller
$users = $this->cache('users', function() use ($userModel) {
    return $userModel->all();
}, 3600);

// Or use Cache facade
$users = $this->cache('users');
if (!$users) {
    $users = $userModel->all();
    $this->cache('users', $users, 3600);
}
```

### Tagged Cache

```php
use System\Cache\CacheHelper;

// Store with tags
$data = CacheHelper::cacheTags()
    ->tag(['users', 'active'])
    ->remember('active_users', 3600, function() {
        return $userModel->where(['status' => 'active'])->get();
    });

// Flush by tag
CacheHelper::flushTag('users');
```

### Cache Statistics

```php
$stats = Cache::getStats();
// Returns: hits, misses, writes, deletes, items_count
```

---

## Security Features

### CSRF Protection

#### Generate Token

```php
use System\Security\Csrf;

$token = Csrf::generateToken();
```

#### In Forms

```php
<form method="POST">
    <input type="hidden" name="_csrf" value="<?= Csrf::getToken() ?>">
    <!-- form fields -->
</form>
```

#### Verify Token

```php
if (Csrf::verifyToken($_POST['_csrf'])) {
    // Token valid
}
```

### XSS Protection

```php
// In controller
$clean = $this->xssClean($userInput);

// In views - always escape output
<?= htmlspecialchars($userInput) ?>
```

### Input Sanitization

```php
// Sanitize email
$email = $this->sanitizeInput($_POST['email'], 'email');

// Sanitize integer
$age = $this->sanitizeInput($_POST['age'], 'int');

// Sanitize float
$price = $this->sanitizeInput($_POST['price'], 'float');

// Sanitize URL
$website = $this->sanitizeInput($_POST['website'], 'url');
```

### Password Hashing

```php
// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verify password
if (password_verify($inputPassword, $hashedPassword)) {
    // Password correct
}
```

---

## Debug & Error Handling

### Debug Toolbar

When `DEBUG_MODE=true` in `.env`, a debug toolbar appears at the bottom showing:

- Execution time
- Memory usage
- Database queries with backtrace
- Route information
- Request details
- System logs

### Logging

```php
// Error logging (automatic)
$this->logError('Error message');

// Debug logging
$this->logDebug('Debug information');
```

Logs are stored in `writable/logs/error_YYYY-MM-DD.log`.

### Error Pages

Custom error pages in `app/Views/errors/`:

- `404.php` - Not Found
- `500.php` - Internal Server Error
- `403.php` - Forbidden

### Production vs Development

**Development (.env):**
```env
APP_ENV=development
APP_DEBUG=true
DEBUG_MODE=true
```

**Production (.env):**
```env
APP_ENV=production
APP_DEBUG=false
DEBUG_MODE=false
```

In production, errors show user-friendly pages instead of debug information.

---

## Best Practices

### 1. Code Organization

```php
// Use namespaces
namespace App\Controllers;

// Follow PSR standards
class UserController extends BaseController
{
    // Clear method names
    public function index() { }
    public function show($id) { }
    public function store() { }
}
```

### 2. Security

```php
// Always validate input
$this->validate($_POST, [
    'email' => 'required|valid_email',
    'password' => 'required|min_length[8]'
]);

// Always escape output
<?= htmlspecialchars($data) ?>

// Use prepared statements (automatic in framework)
$userModel->where(['email' => $email])->first();
```

### 3. Performance

```php
// Use caching for expensive operations
$data = Cache::remember('expensive_query', 3600, function() {
    return $this->complexDatabaseQuery();
});

// Lazy load models
$userModel = $this->model('UserModel');

// Use query builder instead of raw queries
```

### 4. Error Handling

```php
try {
    $result = $userModel->insert('users', $data);
} catch (Exception $e) {
    $this->logError($e->getMessage());
    $this->setFlash('error', 'Operation failed');
    $this->to('/error');
}
```

### 5. Testing

```php
// Test your routes
// Test middleware logic
// Test model methods
// Test controller responses
```

---

## API Reference

### BaseController Methods

| Method | Description |
|--------|-------------|
| `view($view, $data)` | Load view with data |
| `to($url)` | Redirect to URL |
| `model($name, $alias)` | Load model |
| `getPost($key, $default)` | Get POST data |
| `getGet($key, $default)` | Get GET data |
| `getVar($key, $default)` | Get input from any method |
| `validate($data, $rules)` | Validate data |
| `respondWithJSON($data, $code)` | JSON response |
| `setSession($key, $value)` | Set session |
| `getSession($key, $default)` | Get session |
| `setFlash($type, $message)` | Set flash message |
| `uploadFile($field, $ext, $size, $folder)` | Upload file |
| `sanitizeInput($input, $type)` | Sanitize input |
| `xssClean($data)` | Clean XSS |

### BaseModel Methods

| Method | Description |
|--------|-------------|
| `get()` | Get all records |
| `first()` | Get first record |
| `find($id)` | Find by ID |
| `where($conditions)` | Add WHERE clause |
| `orWhere($conditions)` | Add OR WHERE |
| `whereIn($field, $values)` | WHERE IN |
| `like($field, $value)` | LIKE search |
| `join($table, $condition)` | JOIN tables |
| `orderBy($field, $direction)` | Order results |
| `limit($limit, $offset)` | Limit results |
| `insert($table, $data)` | Insert record |
| `update($table, $data, $where)` | Update records |
| `delete($table, $where)` | Delete records |
| `beginTransaction()` | Start transaction |
| `commit()` | Commit transaction |
| `rollBack()` | Rollback transaction |

### Cache Methods

| Method | Description |
|--------|-------------|
| `Cache::get($key, $default)` | Get cached value |
| `Cache::put($key, $value, $ttl)` | Store in cache |
| `Cache::remember($key, $ttl, $callback)` | Get or store |
| `Cache::forget($key)` | Delete cache key |
| `Cache::flush()` | Clear all cache |
| `Cache::has($key)` | Check if exists |

---

## Conclusion

CodeIgniter Alternative Framework provides a modern, lightweight, and powerful foundation for building PHP applications. With its intuitive API, robust features, and excellent performance, you can focus on building great applications rather than dealing with framework complexity.

### Resources

- **Documentation**: This guide
- **Support**: Contact framework author
- **Version**: 2.0.0
- **License**: MIT

### Contributing

Feel free to contribute to the framework by:
- Reporting bugs
- Suggesting features
- Submitting pull requests
- Improving documentation

---

**Happy Coding with CodeIgniter Alternative Framework!** 🚀
