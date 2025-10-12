<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="generator" content="CodeIgniter Alternative Framework">
    <meta name="description" content="CodeIgniter Alternative - A lightweight, fast, and modern PHP framework with MVC architecture">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>CodeIgniter Alternative Framework v2.0.0</title>
    <style>
        :root {
            --ci-primary: #dd4814;
            --ci-primary-dark: #bf3c10;
            --ci-primary-light: #e65c33;
            --ci-secondary: #6c757d;
            --ci-success: #198754;
            --ci-warning: #ffc107;
            --ci-danger: #dc3545;
            --ci-light: #f8f9fa;
            --ci-dark: #212529;
            --ci-border: #dee2e6;
            --ci-bg: #ffffff;
            --ci-text: #212529;
            --ci-text-muted: #6c757d;
            --ci-gradient: linear-gradient(135deg, #dd4814 0%, #e65c33 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: var(--ci-bg);
            color: var(--ci-text);
            line-height: 1.6;
            font-weight: 400;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            padding: 100px 0 80px;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--ci-gradient);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--ci-gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(221, 72, 20, 0.3);
        }

        .logo-text {
            font-size: 2.75rem;
            font-weight: 800;
            background: var(--ci-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.025em;
        }

        .version-badge {
            display: inline-block;
            background: var(--ci-gradient);
            color: white;
            padding: 8px 20px;
            border-radius: 24px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(221, 72, 20, 0.2);
        }

        .tagline {
            font-size: 1.375rem;
            color: var(--ci-text-muted);
            max-width: 700px;
            margin: 0 auto 40px;
            font-weight: 400;
            line-height: 1.7;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: var(--ci-gradient);
            color: white;
            border-color: transparent;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(221, 72, 20, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--ci-text);
            border-color: var(--ci-border);
        }

        .btn-secondary:hover {
            background: var(--ci-light);
            border-color: var(--ci-primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2px;
            background: var(--ci-border);
            margin: 80px 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .stat-item {
            background: var(--ci-bg);
            padding: 50px 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .stat-item:hover {
            background: var(--ci-light);
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 2.75rem;
            font-weight: 800;
            background: var(--ci-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
        }

        .stat-label {
            color: var(--ci-text-muted);
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        
        .section {
            margin: 100px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--ci-text);
            margin-bottom: 48px;
            text-align: center;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--ci-gradient);
            border-radius: 2px;
        }

        .section-subtitle {
            font-size: 1.25rem;
            color: var(--ci-text-muted);
            text-align: center;
            max-width: 700px;
            margin: 0 auto 48px;
            line-height: 1.7;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: var(--ci-bg);
            padding: 40px 32px;
            border: 1px solid var(--ci-border);
            border-radius: 12px;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--ci-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            border-color: var(--ci-primary-light);
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ci-primary);
            font-size: 1.75rem;
            margin: 0 auto 24px;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: var(--ci-gradient);
            color: white;
            transform: scale(1.1);
        }

        .feature-card h3 {
            color: var(--ci-text);
            font-size: 1.375rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .feature-card p {
            color: var(--ci-text-muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .steps {
            max-width: 900px;
            margin: 0 auto;
        }

        .step {
            display: flex;
            gap: 24px;
            margin-bottom: 36px;
            padding: 32px;
            background: var(--ci-bg);
            border: 1px solid var(--ci-border);
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .step::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--ci-gradient);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .step:hover {
            border-color: var(--ci-primary-light);
            transform: translateX(5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .step:hover::before {
            transform: scaleY(1);
        }

        .step-number {
            width: 44px;
            height: 44px;
            background: var(--ci-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(221, 72, 20, 0.3);
        }

        .step-content {
            flex: 1;
        }

        .step-content h4 {
            color: var(--ci-text);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .step-content p {
            color: var(--ci-text-muted);
            font-size: 1rem;
            margin-bottom: 16px;
            line-height: 1.7;
        }

        .code-block {
            background: #1a1d23;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Fira Code', 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            border: 1px solid #2d3748;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .code-block code {
            display: block;
            white-space: pre;
            line-height: 1.5;
        }
        
        .architecture {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 80px 0;
            margin: 100px 0;
            position: relative;
        }

        .architecture::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--ci-gradient);
        }

        .architecture-diagram {
            max-width: 900px;
            margin: 0 auto;
            background: var(--ci-bg);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid var(--ci-border);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }

        .flow-item {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 16px 0;
            background: var(--ci-light);
            border-radius: 8px;
            border: 1px solid var(--ci-border);
            font-weight: 600;
            position: relative;
            transition: all 0.3s ease;
        }

        .flow-item:hover {
            background: white;
            border-color: var(--ci-primary-light);
            transform: translateX(5px);
        }

        .flow-item::after {
            content: '↓';
            position: absolute;
            bottom: -28px;
            color: var(--ci-primary);
            font-size: 1.5rem;
            font-weight: bold;
            z-index: 2;
        }

        .flow-item:last-child::after {
            display: none;
        }

        .flow-item.primary {
            background: var(--ci-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 6px 16px rgba(221, 72, 20, 0.3);
        }

        footer {
            text-align: center;
            padding: 60px 20px;
            color: var(--ci-text-muted);
            border-top: 1px solid var(--ci-border);
            margin-top: 100px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            position: relative;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--ci-gradient);
        }

        footer a {
            color: var(--ci-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: var(--ci-primary-dark);
            text-decoration: underline;
        }

        footer p {
            margin: 12px 0;
            font-size: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin: 24px 0 32px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--ci-text-muted);
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--ci-primary);
            background: rgba(221, 72, 20, 0.05);
            text-decoration: none;
        }

        .system-info {
            background: white;
            padding: 16px 24px;
            border-radius: 8px;
            border: 1px solid var(--ci-border);
            display: inline-block;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            header {
                padding: 80px 0 60px;
            }

            .logo-text {
                font-size: 2.25rem;
            }

            .logo-icon {
                width: 56px;
                height: 56px;
                font-size: 24px;
            }

            .tagline {
                font-size: 1.25rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .section-title {
                font-size: 2.25rem;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .step {
                flex-direction: column;
                gap: 20px;
                padding: 28px;
            }

            .stat-number {
                font-size: 2.25rem;
            }

            .architecture-diagram {
                padding: 32px 24px;
            }

            .flow-item {
                padding: 16px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 16px;
            }

            .logo-text {
                font-size: 2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .feature-card {
                padding: 32px 24px;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-links {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <span class="logo-text">CodeIgniter Alternative</span>
            </div>
            <div class="version-badge">v2.0.0 - Modern PHP Framework</div>
            <p class="tagline">
                A lightweight, fast, and modern PHP MVC framework designed for developers who value simplicity, performance, and clean code architecture.
            </p>
            <div class="cta-buttons">
                <a href="#documentation" class="btn btn-primary">
                    <i class="fas fa-book-open"></i> View documentation
                </a>
                <a href="https://github.com/Inforteuz/codeigniter-alternative" target="_blank" class="btn btn-secondary">
                    <i class="fab fa-github"></i> GitHub repository
                </a>
                <a href="#quick-start" class="btn btn-secondary">
                    <i class="fas fa-rocket"></i> Quick start
                </a>
            </div>
        </header>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-number">PHP 8.1+</div>
                <div class="stat-label">Modern PHP</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">MVC</div>
                <div class="stat-label">Clean Architecture</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">MIT</div>
                <div class="stat-label">Open Source</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">100%</div>
                <div class="stat-label">Custom Built</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Why choose this Framework?</h2>
            <p class="section-subtitle">Built with modern development practices and developer experience in mind</p>
            
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3>MVC Architecture</h3>
                    <p>Clean separation of concerns with Model-View-Controller pattern. Organize your code logically and maintainably.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <h3>Advanced Router</h3>
                    <p>Powerful routing system with dynamic parameters, middleware support, and RESTful routing capabilities.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Security First</h3>
                    <p>Built-in CSRF protection, XSS filtering, SQL injection prevention, and input validation out of the box.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Query Builder</h3>
                    <p>Fluent database query builder with support for complex queries, joins, transactions, and migrations.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3>Middleware System</h3>
                    <p>Flexible middleware pipeline for authentication, CORS, rate limiting, and custom request processing.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>High Performance</h3>
                    <p>Optimized for speed with built-in caching, efficient autoloading, and minimal overhead.</p>
                </div>
            </div>
        </div>

        <div class="architecture">
            <div class="container">
                <h2 class="section-title">Request lifecycle</h2>
                <p class="section-subtitle">Understand how requests flow through your application for better debugging and optimization</p>
                
                <div class="architecture-diagram">
                    <div class="flow-item">HTTP Request</div>
                    <div class="flow-item">Front Controller (index.php)</div>
                    <div class="flow-item">Router & Middleware Processing</div>
                    <div class="flow-item primary">Controller Execution</div>
                    <div class="flow-item">Model & Database Interaction</div>
                    <div class="flow-item">View Rendering</div>
                    <div class="flow-item">HTTP Response</div>
                </div>
            </div>
        </div>

        <div id="quick-start" class="section">
            <h2 class="section-title">Get started in minutes</h2>
            <p class="section-subtitle">Follow these simple steps to start building your application</p>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Download and setup</h4>
                        <p>Get the framework and set up your development environment</p>
                        <div class="code-block">
                            <code># Clone the repository
git clone https://github.com/Inforteuz/codeigniter-alternative.git myapp
cd myapp

# Copy environment configuration
cp .env.example .env

# Set proper permissions
chmod -R 755 writable/</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Configure your application</h4>
                        <p>Update the .env file with your application settings</p>
                        <div class="code-block">
                            <code># Application configuration
APP_NAME="My Awesome App"
APP_ENV=development
APP_DEBUG=true

# Database Configuration  
DB_HOST=localhost
DB_NAME=my_database
DB_USER=root
DB_PASS=password</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Create your first controller</h4>
                        <p>Build your application logic in organized controllers</p>
                        <div class="code-block">
                            <code>&lt;?php
namespace App\Controllers;

use System\BaseController;

class HomeController extends BaseController {
    public function index() {
        $data = [
            'title' => 'Welcome to My App',
            'message' => 'Built with CodeIgniter Alternative Framework'
        ];
        $this->view('home/index', $data);
    }
    
    public function api() {
        $this->respondWithJSON([
            'status' => 'success',
            'message' => 'API endpoint working!'
        ]);
    }
}</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Define your routes</h4>
                        <p>Map URLs to controller methods in app/Routes/Routes.php</p>
                        <div class="code-block">
                            <code>// Basic routes
$router->get('', 'HomeController', 'index');
$router->get('api/test', 'HomeController', 'api');

// Dynamic routes with parameters
$router->get('user/{id}', 'UserController', 'show');
$router->post('user/{id}/update', 'UserController', 'update');

// Route groups with middleware
$router->group(['AuthMiddleware'], function($router) {
    $router->get('dashboard', 'DashboardController', 'index');
    $router->get('profile', 'ProfileController', 'index');
});</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h4>Create your views</h4>
                        <p>Build beautiful interfaces with PHP templates</p>
                        <div class="code-block">
                            <code>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;
    &lt;title&gt;&lt;?= $title ?&gt;&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;h1&gt;&lt;?= htmlspecialchars($title) ?&gt;&lt;/h1&gt;
    &lt;p&gt;&lt;?= htmlspecialchars($message) ?&gt;&lt;/p&gt;
    
    &lt;?php foreach($users as $user): ?&gt;
        &lt;div class="user"&gt;
            &lt;h3&gt;&lt;?= htmlspecialchars($user['name']) ?&gt;&lt;/h3&gt;
            &lt;p&gt;&lt;?= htmlspecialchars($user['email']) ?&gt;&lt;/p&gt;
        &lt;/div&gt;
    &lt;?php endforeach; ?&gt;
&lt;/body&gt;
&lt;/html&gt;</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="documentation" class="section">
            <h2 class="section-title">Comprehensive documentation</h2>
            <p class="section-subtitle">Explore the full capabilities of the framework with our detailed documentation</p>
            
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Getting started</h3>
                    <p>Complete installation guide, configuration, and first steps with the framework.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>API reference</h3>
                    <p>Detailed documentation for all classes, methods, and framework components.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Security guide</h3>
                    <p>Best practices for securing your application and using built-in security features.</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="https://inforte.uz/codeigniter-alternative/" target="_blank" class="btn btn-primary" style="padding: 16px 40px; font-size: 1.1rem;">
                    <i class="fas fa-external-link-alt"></i> View Full Documentation
                </a>
            </div>
        </div>

        <footer>
            <div class="footer-links">
                <a href="https://inforte.uz/codeigniter-alternative/" target="_blank">
                    <i class="fas fa-book"></i> Documentation
                </a>
                <a href="https://github.com/Inforteuz/codeigniter-alternative" target="_blank">
                    <i class="fab fa-github"></i> GitHub
                </a>
                <a href="https://github.com/Inforteuz/codeigniter-alternative/issues" target="_blank">
                    <i class="fas fa-bug"></i> Report issues
                </a>
                <a href="https://inforte.uz" target="_blank">
                    <i class="fas fa-globe"></i> Examples & demos
                </a>
            </div>
            
            <p>Built with ❤️ by <a href="https://inforte.uz" target="_blank">Inforte Team</a></p>
            <p>&copy; <?php echo date("Y"); ?> CodeIgniter Alternative Framework - Version 2.0.0</p>
            
            <div class="system-info">
                <i class="fas fa-server"></i> PHP <?php echo PHP_VERSION; ?> 
                | <i class="fas fa-cube"></i> Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                | <i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </footer>
    </div>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .step, .stat-item, .flow-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>
