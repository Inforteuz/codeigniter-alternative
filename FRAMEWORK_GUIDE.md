# CodeIgniter Alternative Framework - Developer Guide

> **Version:** 2.0.0
> **Author:** Oyatillo (Inforteuz)
> **PHP Required:** 8.1.9+
> **Architecture:** MVC Pattern with Custom Router & Middleware

---

## 📑 Table of Contents

1. [Overview](#-overview)
2. [Framework Architecture](#-framework-architecture)
3. [Directory Structure](#-directory-structure)
4. [Request Lifecycle](#-request-lifecycle)
5. [Getting Started](#-getting-started)
6. [Core Components](#-core-components)
7. [Routing System](#-routing-system)
8. [Controllers](#-controllers)
9. [Models & Database](#-models--database)
10. [Views](#-views)
11. [Middleware System](#-middleware-system)
12. [Security Features](#-security-features)
13. [Configuration & Environment](#-configuration--environment)
14. [Error Handling & Debugging](#-error-handling--debugging)
15. [Deployment Guide](#-deployment-guide)
16. [Enhancement Recommendations](#-enhancement-recommendations)

---

## 🎯 Overview

**CodeIgniter Alternative** adalah framework PHP custom yang dibangun dengan prinsip kesederhanaan dan performa tinggi. Framework ini mengadopsi pola MVC (Model-View-Controller) dengan tambahan sistem middleware dan routing yang fleksibel.

### Key Features

✅ **MVC Architecture** - Clean separation of concerns
✅ **Custom Router** - URL routing dengan parameter dinamis
✅ **Middleware System** - Pre/post request filtering
✅ **Query Builder** - Powerful database abstraction layer
✅ **CSRF Protection** - Built-in security features
✅ **Debug Tools** - Comprehensive debugging interface
✅ **Auto-migration** - Database schema versioning
✅ **Environment Config** - `.env` based configuration

### Design Patterns

- **Singleton Pattern** - Database & Environment classes
- **Front Controller Pattern** - Single entry point (`index.php`)
- **Factory Pattern** - Model & Controller instantiation
- **Middleware Pattern** - Request filtering chain

---

## 🏗 Framework Architecture

```
┌─────────────────────────────────────────────────────┐
│                 HTTP REQUEST                        │
└──────────────────┬──────────────────────────────────┘
                   ↓
         ┌─────────────────────┐
         │    index.php        │ ← Front Controller
         │  (Entry Point)      │
         └──────────┬──────────┘
                    ↓
         ┌─────────────────────┐
         │   autoloader.php    │ ← PSR-4-like Autoloading
         │  (Class Loading)    │
         └──────────┬──────────┘
                    ↓
         ┌─────────────────────┐
         │     Router.php      │ ← Route Matching
         │  (URL Dispatcher)   │
         └──────────┬──────────┘
                    ↓
         ┌─────────────────────┐
         │   Middleware(s)     │ ← Auth, CSRF, Rate Limit
         │  (Request Filter)   │
         └──────────┬──────────┘
                    ↓
         ┌─────────────────────┐
         │    Controller       │ ← Business Logic
         │  (HomeController)   │
         └──────────┬──────────┘
                    ↓
         ┌─────────────────────┐
         │       Model         │ ← Database Operations
         │   (UserModel)       │
         └──────────┬──────────┘
                    ↓
         ┌─────────────────────┐
         │        View         │ ← Presentation Layer
         │   (home/index.php)  │
         └──────────┬──────────┘
                    ↓
┌─────────────────────────────────────────────────────┐
│                 HTTP RESPONSE                       │
└─────────────────────────────────────────────────────┘
```

---

## 📂 Directory Structure

| Directory/File | Purpose | Notes |
|----------------|---------|-------|
| **`app/`** | Application Layer | User-defined code |
| ├─ `Controllers/` | HTTP request handlers | HomeController, UserController |
| ├─ `Models/` | Database models | UserModel, etc. |
| ├─ `Views/` | HTML templates | Blade-like syntax supported |
| ├─ `Middlewares/` | Request filters | Auth, CSRF, RateLimit |
| └─ `Database/` | Migrations & Seeds | Schema versioning |
| **`system/`** | Framework Core | **DO NOT MODIFY** |
| ├─ `Router.php` | Route dispatcher | Matches URLs to controllers |
| ├─ `BaseController.php` | Controller parent class | Common controller methods |
| ├─ `BaseModel.php` | Model parent class | Query builder & ORM-like |
| ├─ `ErrorHandler.php` | Production error pages | Custom 404/500 handlers |
| ├─ `Core/` | Core utilities | Auth, Debug, Env, Middleware |
| ├─ `Database/` | DB connection layer | Singleton PDO wrapper |
| └─ `Security/` | Security helpers | CSRF token management |
| **`writable/`** | Runtime data | **Must be writable (777)** |
| ├─ `logs/` | Error & debug logs | `error_YYYY-MM-DD.log` |
| ├─ `cache/` | Cached data | Route/view cache |
| ├─ `session/` | Session files | If file-based sessions |
| └─ `uploads/` | Uploaded files | User uploads |
| **`tests/`** | Unit & integration tests | PHPUnit tests |
| **`autoloader.php`** | Class autoloader | PSR-4 style autoloading |
| **`index.php`** | Front controller | Entry point for all requests |
| **`.env`** | Environment config | DB credentials, debug mode |
| **`composer.json`** | PHP dependencies | Package management |

---

## 🔄 Request Lifecycle

### Step-by-Step Execution Flow

1. **User Request** → `http://example.com/user/profile`

2. **`index.php`** (Entry Point)
   - Loads `autoloader.php`
   - Initializes `Env::load()` to parse `.env`
   - Sets session cookie with secure flags
   - Checks `APP_DEBUG` mode
   - Runs auto-migrations via `MigrateController`
   - Instantiates `Router` and calls `handleRequest()`

3. **`autoloader.php`** (Class Loading)
   - Registers `spl_autoload_register()`
   - Maps namespaces:
     - `App\Controllers\*` → `app/Controllers/*.php`
     - `App\Models\*` → `app/Models/*.php`
     - `System\*` → `system/*.php`

4. **`Router::handleRequest()`**
   - Parses URL: `/user/profile` → `['user', 'profile']`
   - Matches against registered routes in `Router::__construct()`
   - Extracts route parameters (if dynamic segments like `{id}`)
   - Executes middleware stack (if defined)

5. **Middleware Execution**
   - Example: `AuthMiddleware::handle()`
   - Returns `true` (continue) or `false` (block + redirect)
   - On failure: calls `onFailure()` or `redirectTo()`

6. **Controller Invocation**
   - Instantiates: `new App\Controllers\UserController()`
   - Calls method: `profile()`
   - Controller extends `BaseController` (access to DB, view loader, etc.)

7. **Model Interaction**
   - Controller calls: `$this->model('UserModel')`
   - Model queries database: `$userModel->find($userId)`
   - Returns data to controller

8. **View Rendering**
   - Controller calls: `$this->view('user/profile', ['user' => $userData])`
   - Loads: `app/Views/user/profile.php`
   - Extracts variables with `extract($data)`
   - Outputs HTML

9. **Response Sent** → Browser displays page

---

## 🚀 Getting Started

### 1. Installation

```bash
# Clone repository
git clone https://github.com/Inforteuz/codeigniter-alternative.git
cd codeigniter-alternative

# Install dependencies (if using Composer)
composer install

# Copy environment file
cp .env.example .env
```

### 2. Configuration (`.env`)

```env
# Application
APP_NAME="MyApp"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_HOST=localhost
DB_NAME=my_database
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4

# Timezone
TIMEZONE=Asia/Tashkent
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE my_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Migrations run automatically on every request (see `index.php` line 51).

### 4. File Permissions

```bash
# Linux/macOS
chmod -R 777 writable/
chmod -R 755 system/

# Or more secure approach
chown -R www-data:www-data writable/
chmod -R 755 writable/
```

### 5. Web Server Configuration

**Apache (.htaccess)**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

**Nginx**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## 🧩 Core Components

### 1. Router (`system/Router.php`)

**Purpose:** Maps URLs to controller methods.

**Key Methods:**
- `addRoute($method, $pattern, $controller, $action, $middlewares = [])`
- `handleRequest()` - Processes incoming HTTP request
- `matchRoute($method, $url)` - Finds matching route
- `executeMiddlewares($middlewares)` - Runs middleware chain

**Example: Define Routes**
```php
// In Router::__construct()
$this->addRoute('GET', 'user/{id}', 'UserController', 'show');
$this->addRoute('POST', 'user/update', 'UserController', 'update', ['AuthMiddleware']);
```

**Dynamic Parameters:**
```php
// Route: 'post/{slug}/comment/{id}'
// URL:   /post/hello-world/comment/42
// Params: ['hello-world', '42']

public function showComment($slug, $commentId) {
    // $slug = 'hello-world'
    // $commentId = '42'
}
```

### 2. BaseController (`system/BaseController.php`)

**Purpose:** Parent class for all controllers with helper methods.

**Key Features:**

#### Request Handling
```php
// Get POST data
$email = $this->getPost('email');
$allPost = $this->getPost(); // all POST data

// Get GET data
$page = $this->getGet('page', 1); // default = 1

// Get from any method (POST/GET/PUT/etc)
$name = $this->getVar('name');

// Get JSON input
$data = $this->getJSON();
```

#### Response Methods
```php
// JSON response
$this->respondWithJSON(['status' => 'success'], 200);

// HTTP status responses
$this->respondCreated(['id' => 123]);
$this->respondNoContent();
$this->respondBadRequest('Invalid email');
$this->respondUnauthorized();
$this->respondNotFound('User not found');
```

#### Validation
```php
$this->setValidationRules([
    'email' => 'required|valid_email',
    'password' => 'required|min_length[8]',
    'age' => 'required|integer'
]);

if ($this->validate($_POST)) {
    // Valid
} else {
    $errors = $this->getValidationErrors();
}
```

#### View Loading
```php
$this->view('user/profile', [
    'user' => $userData,
    'title' => 'User Profile'
]);
```

#### Redirects
```php
$this->redirect()->to('/dashboard');
```

### 3. BaseModel (`system/BaseModel.php`)

**Purpose:** Database abstraction with query builder.

**Key Features:**

#### Query Builder
```php
// SELECT
$users = $this->select('id, name, email')
              ->where(['active' => 1])
              ->orderBy('created_at', 'DESC')
              ->limit(10)
              ->get();

// WHERE conditions
$this->where(['status' => 'active'])
     ->orWhere(['role' => 'admin'])
     ->get();

// LIKE search
$this->like('name', 'john')->get();

// IN clause
$this->whereIn('id', [1, 2, 3])->get();

// BETWEEN
$this->between('age', 18, 65)->get();

// JOIN
$this->select('users.*, profiles.bio')
     ->join('profiles', 'profiles.user_id = users.id')
     ->get();

// GROUP BY & HAVING
$this->select('category, COUNT(*) as total')
     ->groupBy('category')
     ->having('total > 5')
     ->get();
```

#### CRUD Operations
```php
// Insert
$id = $this->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update
$this->update('users',
    ['name' => 'Jane Doe'],
    ['id' => 1]
);

// Delete
$this->delete('users', ['id' => 1]);

// Find by ID
$user = $this->find(1);

// Find first
$user = $this->where(['email' => 'john@example.com'])->first();
```

#### Batch Operations
```php
// Insert multiple
$this->insertBatch([
    ['name' => 'User1', 'email' => 'u1@test.com'],
    ['name' => 'User2', 'email' => 'u2@test.com']
]);

// Update multiple
$this->updateBatch([
    ['id' => 1, 'status' => 'active'],
    ['id' => 2, 'status' => 'inactive']
], 'id');
```

### 4. Database (`system/Database/Database.php`)

**Purpose:** Singleton PDO wrapper for database connection.

**Usage:**
```php
$db = Database::getInstance();

// Raw query
$users = $db->fetchAll("SELECT * FROM users WHERE active = ?", [1]);

// Transactions
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

## 🛣 Routing System

### Defining Routes

**Location:** `system/Router.php` → `__construct()` method

```php
public function __construct()
{
    Env::load();

    // Basic routes
    $this->addRoute('GET', '', 'HomeController', 'index');
    $this->addRoute('GET', 'about', 'PageController', 'about');

    // Dynamic segments
    $this->addRoute('GET', 'user/{id}', 'UserController', 'show');
    $this->addRoute('GET', 'post/{slug}/comment/{id}', 'PostController', 'showComment');

    // With middleware
    $this->addRoute('GET', 'dashboard', 'DashboardController', 'index', ['AuthMiddleware']);
    $this->addRoute('POST', 'admin/settings', 'AdminController', 'update', ['AuthMiddleware', 'CsrfMiddleware']);

    // API routes
    $this->addRoute('GET', 'api/user/{id}', 'ApiController', 'getUser');
    $this->addRoute('POST', 'api/user', 'ApiController', 'createUser');
}
```

### Route Parameters

Parameters are extracted and passed to controller methods:

```php
// Route: 'product/{category}/{id}'
// URL:   /product/electronics/42

public function show($category, $id) {
    // $category = 'electronics'
    // $id = '42'
    echo "Category: $category, Product ID: $id";
}
```

### Fallback Routing

If no route matches, Router falls back to dynamic routing:

```
URL: /user/profile/123
     ↓
Controller: UserController
Method: profile()
Params: [123]
```

---

## 🎮 Controllers

### Creating a Controller

**File:** `app/Controllers/UserController.php`

```php
<?php
namespace App\Controllers;

use System\BaseController;
use App\Models\UserModel;

class UserController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $users = $this->userModel->all('users');

        $this->view('user/index', [
            'users' => $users,
            'title' => 'All Users'
        ]);
    }

    public function show($id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->show404();
            return;
        }

        $this->view('user/show', ['user' => $user]);
    }

    public function create()
    {
        if ($this->isMethod('POST')) {
            $data = [
                'name' => $this->getPost('name'),
                'email' => $this->getPost('email'),
                'password' => password_hash($this->getPost('password'), PASSWORD_BCRYPT)
            ];

            $this->setValidationRules([
                'name' => 'required|min_length[3]',
                'email' => 'required|valid_email',
                'password' => 'required|min_length[8]'
            ]);

            if ($this->validate($_POST)) {
                $id = $this->userModel->insert('users', $data);
                $this->setFlash('success', 'User created successfully');
                $this->redirect()->to('/user/' . $id);
            } else {
                $errors = $this->getValidationErrors();
                $this->view('user/create', ['errors' => $errors]);
            }
        } else {
            $this->view('user/create');
        }
    }

    public function update($id)
    {
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->respondNotFound('User not found');
            return;
        }

        $data = [
            'name' => $this->getPost('name'),
            'email' => $this->getPost('email')
        ];

        $this->userModel->update('users', $data, ['id' => $id]);

        $this->respondWithJSON([
            'status' => 'success',
            'message' => 'User updated'
        ]);
    }

    public function delete($id)
    {
        $this->userModel->delete('users', ['id' => $id]);
        $this->redirect()->to('/users');
    }
}
```

---

## 🗄 Models & Database

### Creating a Model

**File:** `app/Models/UserModel.php`

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

    // Validation rules
    protected $validationRules = [
        'email' => 'required|valid_email',
        'name' => 'required|min_length[3]'
    ];

    public function __construct()
    {
        parent::__construct();

        // Add callbacks
        $this->addCallback('beforeInsert', function(&$data) {
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
        });
    }

    /**
     * Get active users
     */
    public function getActiveUsers()
    {
        return $this->select('id, name, email')
                    ->where(['status' => 'active'])
                    ->orderBy('created_at', 'DESC')
                    ->get();
    }

    /**
     * Search users by keyword
     */
    public function searchUsers($keyword)
    {
        return $this->select('*')
                    ->like('name', $keyword, 'both')
                    ->orWhere(['email' => $keyword])
                    ->get();
    }

    /**
     * Get user with profile
     */
    public function getUserWithProfile($userId)
    {
        return $this->select('users.*, profiles.bio, profiles.avatar')
                    ->join('profiles', 'profiles.user_id = users.id', 'LEFT')
                    ->where(['users.id' => $userId])
                    ->first();
    }
}
```

### Database Migrations

**File:** `app/Database/Migrations/2024-01-01-000000_create_users_table.php`

```php
<?php

class CreateUsersTable
{
    public function up()
    {
        $db = \System\Database\Database::getInstance();

        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->execute($sql);
    }

    public function down()
    {
        $db = \System\Database\Database::getInstance();
        $db->execute("DROP TABLE IF EXISTS users");
    }
}
```

---

## 🖼 Views

### Basic View

**File:** `app/Views/home/index.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Home') ?></title>
</head>
<body>
    <h1>Welcome to <?= htmlspecialchars($title) ?></h1>

    <?php if (isset($users) && count($users) > 0): ?>
        <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <a href="/user/<?= $user['id'] ?>">
                    <?= htmlspecialchars($user['name']) ?>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</body>
</html>
```

### Loading Views in Controller

```php
$this->view('home/index', [
    'title' => 'Home Page',
    'users' => $users
]);
```

---

## 🛡 Middleware System

### Available Middleware

| Middleware | Purpose | Usage |
|------------|---------|-------|
| `AuthMiddleware` | Check user authentication | Protected routes |
| `GuestMiddleware` | Check if user is NOT logged in | Login/register pages |
| `CsrfMiddleware` | CSRF token validation | POST/PUT/DELETE requests |
| `CorsMiddleware` | CORS headers | API routes |
| `RateLimitMiddleware` | Rate limiting | Prevent abuse |
| `LanguageMiddleware` | Language detection | Multi-language apps |
| `MaintenanceMiddleware` | Maintenance mode | Site maintenance |

### Creating Custom Middleware

**File:** `app/Middlewares/AdminMiddleware.php`

```php
<?php
namespace App\Middlewares;

use System\BaseController;

class AdminMiddleware extends BaseController
{
    public function handle()
    {
        // Check if user is admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return false;
        }

        return true;
    }

    public function redirectTo()
    {
        return '/403';
    }

    public function onFailure()
    {
        $this->setFlash('error', 'Access denied. Admin only.');
    }
}
```

### Using Middleware in Routes

```php
// Single middleware
$this->addRoute('GET', 'admin/dashboard', 'AdminController', 'index', ['AdminMiddleware']);

// Multiple middlewares
$this->addRoute('POST', 'admin/user/delete', 'AdminController', 'deleteUser', [
    'AuthMiddleware',
    'AdminMiddleware',
    'CsrfMiddleware'
]);
```

---

## 🔐 Security Features

### 1. CSRF Protection

**Generate Token:**
```php
// In your form view
<form method="POST" action="/user/create">
    <input type="hidden" name="_csrf" value="<?= \System\Security\Csrf::generateToken() ?>">
    <!-- Other fields -->
</form>
```

**Verify Token:**
```php
// In CsrfMiddleware (automatic)
// Or manually:
if (\System\Security\Csrf::verifyToken($_POST['_csrf'])) {
    // Valid
}
```

### 2. Input Sanitization

```php
// Sanitize string
$clean = $this->sanitizeInput($_POST['name'], 'string');

// Sanitize email
$email = $this->sanitizeInput($_POST['email'], 'email');

// Sanitize integer
$id = $this->sanitizeInput($_POST['id'], 'int');
```

### 3. XSS Protection

```php
// Clean XSS
$safe = $this->xssClean($userInput);

// In views, always escape output
<?= htmlspecialchars($user['name']) ?>
```

### 4. SQL Injection Prevention

```php
// Always use prepared statements
$stmt = $this->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// Or use query builder (automatic escaping)
$user = $this->where(['email' => $email])->first();
```

---

## ⚙ Configuration & Environment

### Environment Variables (`.env`)

```env
# Application Settings
APP_NAME="My Application"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
TIMEZONE=Asia/Tashkent

# Database Configuration
DB_HOST=localhost
DB_NAME=production_db
DB_USER=db_user
DB_PASS=SecurePassword123
DB_CHARSET=utf8mb4

# Maintenance Mode
APP_MAINTENANCE=false
MAINTENANCE_ESTIMATED_TIME="2 hours"
```

### Accessing Environment Variables

```php
use System\Core\Env;

// Get single value
$dbHost = Env::get('DB_HOST', 'localhost');

// Get all values
$allEnv = Env::getAll();

// Set value at runtime
Env::set('CUSTOM_VAR', 'value');

// Database config
$dbConfig = Env::getDatabaseConfig();
// Returns: ['host' => '...', 'database' => '...', etc.]
```

---

## 🐛 Error Handling & Debugging

### Debug Mode

**Enable in `.env`:**
```env
APP_DEBUG=true
```

**Features:**
- Detailed error pages with stack trace
- Query logging
- Memory usage tracking
- Execution time profiling

### Debug Methods

```php
// Dump and die
$this->dd($data);

// Log debug message
$this->logDebug('Custom debug message');

// Log error
$this->logError('Error occurred');
```

### Error Pages

**Custom Error Pages:** `app/Views/errors/`

- `404.php` - Not Found
- `500.php` - Internal Server Error
- `403.php` - Forbidden

**Example: `404.php`**
```php
<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
</head>
<body>
    <h1>Oops! Page not found.</h1>
    <p>The page you're looking for doesn't exist.</p>
    <a href="/">Go Home</a>
</body>
</html>
```

---

## 🚀 Deployment Guide

### Pre-Deployment Checklist

✅ Set `APP_DEBUG=false` in `.env`
✅ Set `APP_ENV=production`
✅ Configure production database
✅ Set secure passwords
✅ Configure SSL/HTTPS
✅ Set proper file permissions
✅ Enable OPcache
✅ Configure web server

### Apache Configuration

**`.htaccess` (root directory):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect to HTTPS (optional)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Serve existing files/directories directly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Route all other requests to index.php
    RewriteRule ^(.*)$ index.php/$1 [L,QSA]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable directory listing
Options -Indexes

# Protect .env file
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

### Nginx Configuration

**`/etc/nginx/sites-available/yoursite`:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;

    # Redirect HTTP to HTTPS
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name example.com www.example.com;

    root /var/www/html/yourproject;
    index index.php index.html;

    # SSL Certificates
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/yoursite_access.log;
    error_log /var/log/nginx/yoursite_error.log;

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to .env and other sensitive files
    location ~ /\.(env|git|svn|htaccess) {
        deny all;
        return 404;
    }

    # Deny access to writable/logs
    location ~ ^/writable/logs/ {
        deny all;
        return 404;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### File Permissions

```bash
# Application files (read-only)
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Writable directory (for logs, cache, uploads)
chmod -R 775 writable/
chown -R www-data:www-data writable/

# If using SELinux
chcon -R -t httpd_sys_rw_content_t writable/
```

### PHP Configuration (php.ini)

```ini
# Production settings
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

# Performance
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 20M
post_max_size = 25M

# OPcache (recommended)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
```

---

## 📈 Enhancement Recommendations

### Priority 1 (Critical) - Security & Standards

| Enhancement | Description | Benefit | Effort |
|-------------|-------------|---------|--------|
| **PSR-4 Autoloading** | Replace custom autoloader with Composer's PSR-4 | Standard compliance, better performance | Medium |
| **Prepared Statements Everywhere** | Audit all queries to ensure parameterized queries | Prevent SQL injection | Low |
| **Password Hashing** | Use `PASSWORD_ARGON2ID` instead of `PASSWORD_BCRYPT` | Stronger password security | Low |
| **Input Validation Library** | Integrate validation library (e.g., Respect/Validation) | Robust validation | Medium |
| **HTTPS Enforcement** | Force HTTPS in production | Data encryption in transit | Low |
| **Content Security Policy** | Add CSP headers | XSS protection | Medium |
| **Rate Limiting Improvement** | Store rate limits in Redis/Memcached instead of session | Scalable rate limiting | High |

### Priority 2 (Important) - Performance & Scalability

| Enhancement | Description | Benefit | Effort |
|-------------|-------------|---------|--------|
| **Route Caching** | Cache compiled routes to file | Faster route matching | Medium |
| **Query Caching** | Implement query result caching | Reduce DB load | Medium |
| **Lazy Loading Models** | Load models only when needed | Reduced memory usage | Low |
| **Database Connection Pooling** | Use persistent connections | Better DB performance | Low |
| **View Caching** | Cache compiled views | Faster page rendering | Medium |
| **Asset Pipeline** | Minify and combine CSS/JS | Faster page loads | High |

### Priority 3 (Nice to Have) - Developer Experience

| Enhancement | Description | Benefit | Effort |
|-------------|-------------|---------|--------|
| **CLI Tool (Artisan-like)** | Command-line tool for tasks | Easier development workflow | High |
| **ORM Layer** | Implement full ORM (like Eloquent) | More intuitive database operations | High |
| **Template Engine** | Integrate Twig or Blade | Cleaner view syntax | Medium |
| **Dependency Injection** | Add DI container | Better code organization | High |
| **Event System** | Implement event dispatcher | Decoupled architecture | Medium |
| **API Resource Classes** | Transform models to API responses | Consistent API responses | Medium |
| **Testing Suite** | Add unit/integration tests | Code quality assurance | High |
| **Documentation Generator** | Auto-generate API docs | Better documentation | Medium |

### Specific Code Improvements

#### 1. Replace Custom Autoloader with Composer PSR-4

**Current (`autoloader.php`):**
```php
spl_autoload_register(function ($class) {
    // Custom logic...
});
```

**Recommended (`composer.json`):**
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "System\\": "system/"
        }
    }
}
```

Then run: `composer dump-autoload`

In `index.php`:
```php
require_once 'vendor/autoload.php'; // Instead of autoloader.php
```

#### 2. Environment-Based Configuration

**Create:** `system/Config/Config.php`
```php
<?php
namespace System\Config;

class Config
{
    private static $config = [];

    public static function load($file)
    {
        $path = __DIR__ . "/../../config/{$file}.php";
        if (file_exists($path)) {
            self::$config[$file] = require $path;
        }
    }

    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
```

**Create:** `config/database.php`
```php
<?php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'host' => env('DB_HOST', 'localhost'),
            'database' => env('DB_NAME'),
            'username' => env('DB_USER'),
            'password' => env('DB_PASS'),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
        ]
    ]
];
```

#### 3. CLI Tool (Spark-like Command Runner)

**Enhance `spark` file:**
```php
#!/usr/bin/env php
<?php

require_once 'autoloader.php';

use System\Core\Env;

Env::load();

// Command registry
$commands = [
    'migrate' => 'App\\Commands\\MigrateCommand',
    'make:controller' => 'App\\Commands\\MakeControllerCommand',
    'make:model' => 'App\\Commands\\MakeModelCommand',
    'serve' => 'App\\Commands\\ServeCommand',
];

$command = $argv[1] ?? 'help';

if ($command === 'help') {
    echo "Available commands:\n";
    foreach (array_keys($commands) as $cmd) {
        echo "  php spark {$cmd}\n";
    }
    exit(0);
}

if (!isset($commands[$command])) {
    echo "Unknown command: {$command}\n";
    exit(1);
}

$commandClass = $commands[$command];
$commandInstance = new $commandClass();
$commandInstance->handle(array_slice($argv, 2));
```

**Example Command:** `app/Commands/MakeControllerCommand.php`
```php
<?php
namespace App\Commands;

class MakeControllerCommand
{
    public function handle($args)
    {
        $name = $args[0] ?? null;

        if (!$name) {
            echo "Usage: php spark make:controller ControllerName\n";
            return;
        }

        $template = "<?php\nnamespace App\\Controllers;\n\nuse System\\BaseController;\n\nclass {$name} extends BaseController\n{\n    public function index()\n    {\n        // Your code here\n    }\n}\n";

        $filePath = "app/Controllers/{$name}.php";
        file_put_contents($filePath, $template);

        echo "Controller created: {$filePath}\n";
    }
}
```

---

## 🎓 Best Practices

### 1. Controller Best Practices

✅ Keep controllers thin - business logic in models/services
✅ Use dependency injection for models
✅ Return consistent response formats
✅ Validate input before processing
✅ Use transactions for multi-step operations

### 2. Model Best Practices

✅ Use query builder instead of raw SQL
✅ Define relationships in separate methods
✅ Use soft deletes for user data
✅ Implement caching for frequently accessed data
✅ Use model events for logging/auditing

### 3. Security Best Practices

✅ Never trust user input - always validate and sanitize
✅ Use CSRF tokens for state-changing requests
✅ Store passwords with strong hashing (Argon2id)
✅ Implement rate limiting on authentication endpoints
✅ Use HTTPS in production
✅ Keep dependencies updated

### 4. Performance Best Practices

✅ Use database indexes on frequently queried columns
✅ Cache expensive queries
✅ Lazy load relationships
✅ Optimize images and assets
✅ Enable OPcache in production
✅ Use CDN for static assets

---

## 📚 Additional Resources

### Documentation
- PHP Official: https://www.php.net/manual/en/
- PDO Reference: https://www.php.net/manual/en/book.pdo.php
- PSR Standards: https://www.php-fig.org/psr/

### Similar Frameworks (for inspiration)
- CodeIgniter 4: https://codeigniter.com/user_guide/
- Laravel: https://laravel.com/docs
- Slim Framework: https://www.slimframework.com/

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/AmazingFeature`
3. Commit changes: `git commit -m 'Add AmazingFeature'`
4. Push to branch: `git push origin feature/AmazingFeature`
5. Open a Pull Request

---

## 📝 License

This framework is open-source software. Please check the repository for license details.

---

## 👨‍💻 Author

**Oyatillo (Inforteuz)**
GitHub: [@Inforteuz](https://github.com/Inforteuz)

---

**Built with ❤️ for the PHP community**
