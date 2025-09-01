# 🚀 CodeIgniter-Style PHP MVC Framework

A lightweight and modern MVC framework inspired by CodeIgniter 4, built from scratch using pure PHP and following clean architectural principles.

---

## 📌 Features

- ✅ **MVC Architecture** (Model-View-Controller)
- ⚙️ **Environment Configuration** via `.env`
- 💾 **Database Abstraction** using PDO
- 🔁 **Flexible Routing System** with Middleware support
- 🛡️ **Middleware System** for Authentication, CORS, Rate-Limiting
- 🔐 **Session Management** (Secure & Customizable)
- 🐞 **Robust Error Handling & Debugging**
- 🧪 **Built-in Migration and Seeding**
- 🧩 **API-Ready Structure**

---

## 🛠️ Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/yourname/your-framework.git
Set up the .env file:

Rename .env.example to .env and configure your environment:

env
Копировать код
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

arduino
Копировать код
http://localhost/migrate
This will automatically create the necessary tables.

📁 Folder Structure
pgsql
Копировать код
project/
├── app/
│   ├── Controllers/        # Application logic (e.g., HomeController.php)
│   ├── Models/             # Data models (e.g., UserModel.php)
│   ├── Middlewares/        # Middleware classes (e.g., AuthMiddleware.php)
│   └── Views/              # View files (HTML templates)
├── system/
│   ├── Core/               # Framework core (e.g., Env, Middleware loader)
│   ├── Database/           # PDO wrapper and DB tools
│   ├── Router.php          # Routing and dispatch logic
│   ├── BaseController.php  # Parent controller class
│   ├── BaseModel.php       # Parent model class
│   └── ErrorHandler.php    # Global error and exception handling
├── scripts/                # SQL migrations and seeders
├── public/                 # Public assets (css, js, images)
├── .env                    # Environment config
├── autoloader.php          # Custom PSR-4 autoloader
└── index.php               # Entry point (front controller)
