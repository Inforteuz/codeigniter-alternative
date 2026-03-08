<?php

/**
 * Application Routes
 *
 * This file contains all the route definitions for your application.
 * Routes are registered using the Router instance methods.
 *
 * @package    CodeIgniter Alternative
 * @subpackage App
 * @version    1.0.0
 * @date       2024-12-01
 */

// ====================================================================
//  ROUTE DEFINITION GUIDE
// ====================================================================
// 
// Available router methods:
// 
// • $router->get($uri, $controller, $method, [$middlewares])
// • $router->post($uri, $controller, $method, [$middlewares]) 
// • $router->put($uri, $controller, $method, [$middlewares])
// • $router->delete($uri, $controller, $method, [$middlewares])
// • $router->patch($uri, $controller, $method, [$middlewares])
// • $router->group([$middlewares], function($router) { ... })
//
// Dynamic parameters: {id}, {slug}, {category}, etc.
// Middleware protection: ['AuthMiddleware'], ['AdminMiddleware'], etc.
// Route groups: For organizing related routes with shared middleware
// ====================================================================

// ====================================================================
//  PUBLIC ROUTES (Guest access)
// ====================================================================

$router->get('', 'HomeController', 'index');

// ====================================================================
//  FALLBACK ROUTE (Must be last - 404 Handler)
// ====================================================================

$router->get('{any}', 'ErrorController', 'notFound');

?>
