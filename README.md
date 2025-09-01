# PHP MVC Framework - CodeIgniter 4 Style

Bu framework CodeIgniter 4 ga o'xshash tarzda yaratilgan PHP MVC framework.

## Xususiyatlari

- **MVC Architecture** - Model, View, Controller pattern
- **Environment Configuration** - .env fayl orqali sozlash
- **Database Abstraction** - PDO asosida
- **Routing System** - Flexible routing with middleware support
- **Middleware Support** - Authentication va boshqa middleware'lar
- **Session Management** - Xavfsiz session boshqaruvi
- **Error Handling** - To'liq error handling va logging

## O'rnatish

1. Loyihani yuklab oling
2. `.env` faylini sozlang
3. Database yarating
4. `/migrate` ga tashrif buyuring (database jadvallarini yaratish uchun)

## Foydalanish

### Controllers

\`\`\`php
<?php
namespace App\Controllers;

use System\BaseController;

class MyController extends BaseController
{
    public function index()
    {
        $this->view('my_view', ['data' => 'value']);
    }
}
?>
\`\`\`

### Models

\`\`\`php
<?php
namespace App\Models;

use System\BaseModel;

class MyModel extends BaseModel
{
    protected $table = 'my_table';
    
    public function getAllRecords()
    {
        return $this->all();
    }
}
?>
\`\`\`

### Routes

Routes avtomatik ravishda `controller/method/params` formatida ishlaydi.

Maxsus route'lar uchun `system/Router.php` da qo'shing:

\`\`\`php
$this->addRoute('GET', 'custom-url', 'MyController', 'myMethod', ['AuthMiddleware']);
\`\`\`

### Middleware

\`\`\`php
<?php
namespace App\Middlewares;

use System\BaseController;

class MyMiddleware extends BaseController
{
    public function handle()
    {
        // Middleware logic
        return true; // yoki false
    }
    
    public function redirectTo()
    {
        return '/login';
    }
}
?>
\`\`\`

## Environment Variables

`.env` faylida quyidagi o'zgaruvchilarni sozlang:

\`\`\`
APP_NAME=MyApp
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_NAME=mydb
DB_USER=root
DB_PASS=password
\`\`\`

## Default Login

- Username: `admin`
- Password: `admin123`

## Folder Structure

\`\`\`
/
├── app/
│   ├── Controllers/
│   ├── Models/
│   ├── Middlewares/
│   └── Views/
├── system/
│   ├── Core/
│   ├── BaseController.php
│   ├── BaseModel.php
│   ├── Database.php
│   └── Router.php
├── .env
└── index.php
