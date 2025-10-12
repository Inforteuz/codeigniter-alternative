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
$router->get('test', 'HomeController', 'test');

// Logout Route (Accessible to all authenticated users)
$router->get('logout', 'HomeController', 'logout');
$router->post('logout', 'HomeController', 'logout');

// ====================================================================
//  PROTECTED ROUTES (Authentication required)
// ====================================================================

$router->get('dashboard', 'DashboardController', 'index', ['AuthMiddleware']);

// ====================================================================
//  API ROUTES (RESTful endpoints)
// ====================================================================

$router->get('api/user', 'ApiController', 'getUser');
$router->post('api/user', 'ApiController', 'updateUser');

// ====================================================================
//  STATIC PAGES (Informational content)
// ====================================================================

// $router->get('about', 'PageController', 'about');
// $router->get('contact', 'PageController', 'contact');
// $router->post('contact', 'PageController', 'sendMessage');

// ====================================================================
//  USER PROFILE ROUTES (Protected user area)
// ====================================================================

// $router->get('profile', 'ProfileController', 'index', ['AuthMiddleware']);
// $router->post('profile', 'ProfileController', 'update', ['AuthMiddleware']);

// ====================================================================
//  ERROR PAGES (Custom error handling)
// ====================================================================

// $router->get('error/403', 'ErrorController', 'forbidden');
// $router->get('error/404', 'ErrorController', 'notFound');
// $router->get('error/500', 'ErrorController', 'serverError');

// ====================================================================
//  INTERNATIONALIZATION (Multi-language support)
// ====================================================================

// $router->get('change-language/{lang}', 'LanguageController', 'change');

// ====================================================================
//  ADMIN ROUTES GROUP (Elevated privileges required)
// ====================================================================
/*
$router->group(['AuthMiddleware', 'AdminMiddleware'], function($router) {
    $router->get('admin', 'AdminController', 'dashboard');
    $router->get('admin/users', 'AdminController', 'users');
    $router->get('admin/users/{id}', 'AdminController', 'userDetail');
    $router->post('admin/users/{id}', 'AdminController', 'updateUser');
    $router->delete('admin/users/{id}', 'AdminController', 'deleteUser');
});
*/

// ====================================================================
//  API ROUTES GROUP (CORS enabled)
// ====================================================================
/*
$router->group(['CorsMiddleware'], function($router) {
    $router->get('api/posts', 'ApiController', 'getPosts');
    $router->get('api/posts/{id}', 'ApiController', 'getPost');
    $router->post('api/posts', 'ApiController', 'createPost');
    $router->put('api/posts/{id}', 'ApiController', 'updatePost');
    $router->delete('api/posts/{id}', 'ApiController', 'deletePost');
});
*/

// ====================================================================
//  BLOG ROUTES (Content management)
// ====================================================================

// $router->get('blog', 'BlogController', 'index');
// $router->get('blog/{slug}', 'BlogController', 'show');
// $router->get('blog/category/{category}', 'BlogController', 'category');
// $router->get('blog/tag/{tag}', 'BlogController', 'tag');

// ====================================================================
//  PRODUCT ROUTES (E-commerce features)
// ====================================================================

// $router->get('products', 'ProductController', 'index');
// $router->get('products/{id}', 'ProductController', 'show');
// $router->get('products/category/{category}', 'ProductController', 'category');

// ====================================================================
//  USER MANAGEMENT ROUTES (Protected user operations)
// ====================================================================

// $router->get('users', 'UserController', 'index', ['AuthMiddleware']);
// $router->get('users/{id}', 'UserController', 'show', ['AuthMiddleware']);
// $router->get('users/{id}/edit', 'UserController', 'edit', ['AuthMiddleware']);
// $router->post('users/{id}', 'UserController', 'update', ['AuthMiddleware']);

// ====================================================================
//  FILE MANAGEMENT ROUTES (Upload & download operations)
// ====================================================================

// $router->get('upload', 'UploadController', 'index', ['AuthMiddleware']);
// $router->post('upload', 'UploadController', 'store', ['AuthMiddleware']);
// $router->get('files/{filename}', 'FileController', 'show');

// ====================================================================
//  SEARCH ROUTES (Content discovery)
// ====================================================================

// $router->get('search', 'SearchController', 'index');
// $router->get('search/{query}', 'SearchController', 'results');

// ====================================================================
//  SEO ROUTES (Search engine optimization)
// ====================================================================

// $router->get('sitemap.xml', 'SeoController', 'sitemap');
// $router->get('robots.txt', 'SeoController', 'robots');

// ====================================================================
//  CUSTOM PROJECT ROUTES (Application specific)
// ====================================================================

// $router->get('projects', 'ProjectController', 'index');
// $router->get('projects/create', 'ProjectController', 'create', ['AuthMiddleware']);
// $router->post('projects', 'ProjectController', 'store', ['AuthMiddleware']);
// $router->get('projects/{id}', 'ProjectController', 'show');
// $router->get('projects/{id}/edit', 'ProjectController', 'edit', ['AuthMiddleware']);
// $router->put('projects/{id}', 'ProjectController', 'update', ['AuthMiddleware']);
// $router->delete('projects/{id}', 'ProjectController', 'delete', ['AuthMiddleware']);

// ====================================================================
//  NEWSLETTER ROUTES (Email subscription management)
// ====================================================================

// $router->get('newsletter', 'NewsletterController', 'index');
// $router->post('newsletter/subscribe', 'NewsletterController', 'subscribe');
// $router->get('newsletter/unsubscribe/{token}', 'NewsletterController', 'unsubscribe');

// ====================================================================
//  FALLBACK ROUTE (Must be last - 404 Handler)
// ====================================================================

$router->get('{any}', 'ErrorController', 'notFound');

?>