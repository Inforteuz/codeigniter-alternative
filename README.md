# PHP MVC Framework - CodeIgniter 4 Style

A modern PHP MVC framework built with CodeIgniter 4 style architecture. Designed for creating simple, fast, and scalable web applications with clean code structure.

## 🚀 Features

- **MVC Architecture** - Model, View, Controller pattern implementation
- **Environment Configuration** - Easy setup through .env file
- **Database Abstraction** - Secure database operations with PDO
- **Routing System** - Flexible routing with middleware support
- **Middleware Support** - Authentication and custom middleware
- **Session Management** - Secure session handling
- **Error Handling** - Complete error handling and logging system
- **Auto-loading** - PSR-4 based automatic class loading
- **Security Features** - CSRF protection and XSS filtering

## 📦 Installation

### Requirements
- PHP 7.4+ or higher
- Composer (optional)
- MySQL/MariaDB/PostgreSQL

### Installation Steps

1. **Clone the repository**
```bash
git clone https://github.com/username/php-mvc-framework.git
cd php-mvc-framework
```

2. **Setup environment file**
```bash
cp .env.example .env
```

3. **Configure database settings**
Edit the `.env` file with your database credentials

4. **Create database and run migrations**
Visit `/migrate` in your browser or run:
```bash
php migrate.php
```

5. **Start development server**
```bash
php -S localhost:8000
```

## 🎯 Usage

### Controllers

Controllers are located in `app/Controllers` directory:

```php
<?php
namespace App\Controllers;

use System\BaseController;

class HomeController extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'Home Page',
            'message' => 'Welcome to our MVC Framework!'
        ];
        
        return $this->view('home/index', $data);
    }
    
    public function about()
    {
        return $this->view('pages/about');
    }
}
```

### Models

Models are located in `app/Models` directory:

```php
<?php
namespace App\Models;

use System\BaseModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    
    public function getAllUsers()
    {
        return $this->all();
    }
    
    public function getUserById($id)
    {
        return $this->find($id);
    }
    
    public function createUser($data)
    {
        return $this->insert($data);
    }
    
    public function updateUser($id, $data)
    {
        return $this->update($id, $data);
    }
}
```

### Views

Views are located in `app/Views` directory using PHP templates:

```php
<!-- app/Views/home/index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MVC Framework' ?></title>
</head>
<body>
    <header>
        <h1><?= $title ?></h1>
    </header>
    
    <main>
        <p><?= $message ?></p>
    </main>
</body>
</html>
```

### Routes

Routes work automatically with `controller/method/params` format. For custom routes, add them in `system/Router.php`:

```php
// Custom routes
$this->addRoute('GET', 'dashboard', 'DashboardController', 'index', ['AuthMiddleware']);
$this->addRoute('POST', 'api/users', 'ApiController', 'createUser');
$this->addRoute('GET', 'profile/{id}', 'UserController', 'profile');
```

### Middleware

Create middleware in `app/Middlewares` directory:

```php
<?php
namespace App\Middlewares;

use System\BaseController;

class AuthMiddleware extends BaseController
{
    public function handle()
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        return true;
    }
    
    public function redirectTo()
    {
        return '/login';
    }
}
```

## ⚙️ Environment Variables

Configure the following variables in your `.env` file:

```env
# Application Settings
APP_NAME=MyApplication
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_HOST=localhost
DB_NAME=mvc_framework
DB_USER=root
DB_PASS=password
DB_CHARSET=utf8mb4

# Session Configuration
SESSION_NAME=mvc_session
SESSION_LIFETIME=7200

# Security
CSRF_PROTECTION=true
```

## 🔐 Default Credentials

- **Username:** `admin`
- **Password:** `admin123`

> **Note:** Please change these credentials after first login for security purposes.

## 📁 Directory Structure

```
/
├── app/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   └── HomeController.php
│   ├── Models/
│   │   └── UserModel.php
│   ├── Middlewares/
│   │   └── AuthMiddleware.php
│   └── Views/
│       ├── layouts/
│       └── home/
├── system/
│   ├── Core/
│   │   ├── Application.php
│   │   └── Config.php
│   ├── BaseController.php
│   ├── BaseModel.php
│   ├── Database.php
│   └── Router.php
├── public/
│   ├── css/
│   ├── js/
│   └── images/
├── .env.example
├── .env
├── .htaccess
├── migrate.php
├── composer.json
└── index.php
```

## 🛠️ Available Methods

### BaseController Methods
- `view($view, $data = [])` - Load view with data
- `redirect($url)` - Redirect to URL
- `json($data)` - Return JSON response
- `input($key, $default = null)` - Get input data

### BaseModel Methods
- `all()` - Get all records
- `find($id)` - Find by primary key
- `where($column, $value)` - Add WHERE condition
- `insert($data)` - Insert new record
- `update($id, $data)` - Update existing record
- `delete($id)` - Delete record

## 🚦 HTTP Status Codes

The framework handles common HTTP status codes:
- `200` - OK
- `404` - Not Found
- `500` - Internal Server Error
- `302` - Redirect

## 🔧 Configuration

### Database Configuration
Configure your database in the `.env` file or modify `system/Database.php` for advanced settings.

### Routing Configuration
Custom routes can be added in `system/Router.php` or create a separate routes file.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

If you have any questions or need help, please:
- Open an issue on GitHub
- Check the documentation
- Contact the maintainer

## 🙏 Acknowledgments

- Inspired by CodeIgniter 4 framework
- Built with modern PHP best practices
- Community contributions welcome

---

**Happy Coding!** 🎉
