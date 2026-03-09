<?php

/**
 * =========================================================
 * CodeIgniter Alternative Framework - index file
 * =========================================================
 * 
 * Developer: Oyatillo
 * Framework Version: 3.0.0
 * Framework Name: CodeIgniter Alternative
 * 
 * This file serves as the entry point of the framework.
 * Here, all incoming requests are routed through the Router
 * system to the appropriate controller or function.
 * 
 * About the framework:
 * CodeIgniter Alternative is a lightweight and fast PHP MVC framework
 * as an alternative to CodeIgniter, designed to make your projects
 * simpler and more organized.
 * 
 * All rights reserved © Oyatillo, 2024
 * =========================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'autoloader.php';

use System\Core\Env;
use System\Core\Debug;
use System\Core\DebugToolbar;
use System\Router;
use System\ErrorHandler;

Env::load();

if (Env::get('APP_ENV') !== 'production') {
    require_once 'app/Controllers/MigrateController.php';
}

// --- PREPARE SESSION ---
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/writable/session';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);

    $cookieName = Env::get('SESSION_NAME', 'ci4_session');
    $cookieSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    session_name($cookieName);
    session_set_cookie_params([
        'lifetime' => (int)Env::get('SESSION_LIFETIME', 7200),
        'path'     => Env::get('SESSION_PATH', '/'),
        'domain'   => Env::get('SESSION_DOMAIN', ''),
        'secure'   => $cookieSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

DebugToolbar::init();
ErrorHandler::register();

if (Env::get('APP_DEBUG') === 'true') {
    Debug::init();
}

$router = new Router();
$router->handleRequest();

if (Env::get('DEBUG_MODE') === 'true') {
    echo DebugToolbar::render();
}
?>
