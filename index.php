<?php
/**
 * =========================================================
 * CodeIgniter Alternative Framework - index file
 * =========================================================
 * 
 * Developer: Oyatillo
 * Framework Version: 1.0.0
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

require_once 'autoloader.php';
require_once 'app/Controllers/MigrateController.php';

use System\Core\Env;
use System\Core\Debug;
use System\Router;
use System\ErrorHandler;

Env::load();

if (Env::get('APP_DEBUG') === 'true') {
    Debug::init();
} else {
    ErrorHandler::register();
}

$migrateController = new \App\Controllers\MigrateController();
$migrateController->migrate(); 

$router = new Router();
$router->handleRequest();
?>