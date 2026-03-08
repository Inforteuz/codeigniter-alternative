# 🚀 CodeIgniter-Style PHP MVC Framework

A lightweight and modern MVC framework inspired by CodeIgniter 4, built from scratch using pure PHP and following clean architectural principles.

---

## 📌 Features

- ✅ **MVC Architecture** (Model-View-Controller)
- ⚙️ **Environment Configuration** via `.env`
- 💾 **Database Abstraction** using PDO (MySQL & SQLite support)
- 🔁 **Advanced Router** with Middleware pipeline and Route Groups
- 🛡️ **Middleware System** for Authentication, CORS, Rate-Limiting, CSRF 
- 🔐 **Session Management** (Secure, Centralized Lifecycle)
- 🐞 **Robust Error Handling & Debugging Toolbar**
- 🎨 **Enhanced View Engine** with Extends/Sections and Global Composers
- 🛠 **Built-in Developer CLI** (`bin/framework`) for generating code & migrating
- 🧩 **API-Ready Structure**

---

## 🛠️ Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/Inforteuz/codeigniter-alternative.git
Set up the .env file:

Rename .env.example to .env and configure your environment:
```env
APP_NAME=MyApp
APP_ENV=development
APP_DEBUG=true

DB_HOST=localhost
DB_NAME=my_database
DB_USER=root
DB_PASS=password
Create your database.

Run migrations:

Visit:
http://localhost/migrate
This will automatically create the necessary tables.

📁 Folder Structure
project/
├── app/
│   ├── Composers/          # Global View Composers
│   ├── Controllers/        # Application logic (e.g., HomeController.php)
│   ├── Core/               # App-level Core Services (DI Container, View Engine, Middleware Pipeline)
│   ├── Models/             # Data models (e.g., UserModel.php)
│   ├── Middlewares/        # Middleware classes (e.g., AuthMiddleware.php)
│   └── Views/              # View files (HTML templates)
├── system/
│   ├── Core/               # Framework core (e.g., Env, Config)
│   ├── Database/           # PDO wrapper and Schema/Blueprint tools
│   ├── Router.php          # Routing and dispatch logic
│   ├── BaseController.php  # Parent controller class
│   ├── BaseModel.php       # Parent model class
│   ├── Validation.php      # Data Validator
│   └── ErrorHandler.php    # Global error and exception handling
├── bin/                    # Command Line Interface (framework CLI)
├── database/               # SQL migrations, seeders, and sqlite DB
├── public/                 # Public assets (css, js, images)
├── .env                    # Environment config
├── autoloader.php          # Custom PSR-4 autoloader
└── index.php               # Entry point (front controller)
