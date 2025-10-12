<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="generator" content="CodeIgniter">
    <meta name="description" content="CodeIgniter Alternative - A lightweight, fast, and elegant PHP framework with MVC architecture">
    <link rel="icon" href="favicon.ico" type="image/png">
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            padding: 80px 0 60px;
            text-align: center;
            border-bottom: 1px solid var(--ci-border);
            background: var(--ci-light);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 56px;
            height: 56px;
            background: var(--ci-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--ci-primary);
            letter-spacing: -0.025em;
        }

        .version-badge {
            display: inline-block;
            background: var(--ci-primary);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .tagline {
            font-size: 1.25rem;
            color: var(--ci-text-muted);
            max-width: 600px;
            margin: 0 auto 32px;
            font-weight: 400;
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
            gap: 8px;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .btn-primary {
            background: var(--ci-primary);
            color: white;
            border-color: var(--ci-primary);
        }

        .btn-primary:hover {
            background: var(--ci-primary-dark);
            border-color: var(--ci-primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--ci-text);
            border-color: var(--ci-border);
        }

        .btn-secondary:hover {
            background: var(--ci-light);
            border-color: var(--ci-primary);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1px;
            background: var(--ci-border);
            margin: 60px 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .stat-item {
            background: var(--ci-bg);
            padding: 40px 20px;
            text-align: center;
            transition: all 0.2s ease;
        }
        
        .stat-item:hover {
            background: var(--ci-light);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--ci-primary);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--ci-text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .section {
            margin: 80px 0;
        }

        .section-title {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--ci-text);
            margin-bottom: 40px;
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: var(--ci-text-muted);
            text-align: center;
            max-width: 600px;
            margin: 0 auto 40px;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background: var(--ci-bg);
            padding: 32px;
            border: 1px solid var(--ci-border);
            border-radius: 8px;
            transition: all 0.2s ease;
            text-align: center;
        }

        .feature-card:hover {
            border-color: var(--ci-primary);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            background: var(--ci-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--ci-primary);
            font-size: 1.5rem;
            margin: 0 auto 20px;
        }

        .feature-card h3 {
            color: var(--ci-text);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: var(--ci-text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .steps {
            max-width: 800px;
            margin: 0 auto;
        }

        .step {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 24px;
            background: var(--ci-bg);
            border: 1px solid var(--ci-border);
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .step:hover {
            border-color: var(--ci-primary);
        }

        .step-number {
            width: 36px;
            height: 36px;
            background: var(--ci-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-content h4 {
            color: var(--ci-text);
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .step-content p {
            color: var(--ci-text-muted);
            font-size: 0.95rem;
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            overflow-x: auto;
            border: 1px solid #4a5568;
        }

        .code-block code {
            display: block;
            white-space: pre;
        }
        
        .architecture {
            background: var(--ci-light);
            padding: 60px 0;
            margin: 80px 0;
        }

        .architecture-diagram {
            max-width: 800px;
            margin: 0 auto;
            background: var(--ci-bg);
            padding: 32px;
            border-radius: 8px;
            border: 1px solid var(--ci-border);
        }

        .flow-item {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            margin: 12px 0;
            background: var(--ci-light);
            border-radius: 6px;
            border: 1px solid var(--ci-border);
            font-weight: 500;
            position: relative;
        }

        .flow-item::after {
            content: '↓';
            position: absolute;
            bottom: -24px;
            color: var(--ci-text-muted);
            font-size: 1.25rem;
        }

        .flow-item:last-child::after {
            display: none;
        }

        .flow-item.primary {
            background: var(--ci-primary);
            color: white;
            border-color: var(--ci-primary);
        }

        footer {
            text-align: center;
            padding: 40px 20px;
            color: var(--ci-text-muted);
            border-top: 1px solid var(--ci-border);
            margin-top: 80px;
            background: var(--ci-light);
        }

        footer a {
            color: var(--ci-primary);
            text-decoration: none;
            font-weight: 500;
        }

        footer a:hover {
            text-decoration: underline;
        }

        footer p {
            margin: 8px 0;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--ci-text-muted);
        }

        .footer-links a:hover {
            color: var(--ci-primary);
        }

        @media (max-width: 768px) {
            header {
                padding: 60px 0 40px;
            }

            .logo-text {
                font-size: 2rem;
            }

            .logo-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .tagline {
                font-size: 1.125rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }

            .section-title {
                font-size: 1.875rem;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .step {
                flex-direction: column;
                gap: 16px;
                padding: 20px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .architecture-diagram {
                padding: 24px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 16px;
            }

            .logo-text {
                font-size: 1.75rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .feature-card {
                padding: 24px 20px;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <span class="logo-text">CodeIgniter Alternative</span>
            </div>
            <div class="version-badge">Version 2.0.0 </div>
            <p class="tagline">
                A lightweight, fast, and elegant PHP framework designed for modern web development with MVC architecture.
            </p>
            <div class="cta-buttons">
                <a href="https://inforte.uz/codeigniter-alternative/" target="_blank" class="btn btn-primary">
                    <i class="fas fa-book"></i> Documentation
                </a>
                <a href="https://github.com/Inforteuz/codeigniter-alternative" target="_blank" class="btn btn-secondary">
                    <i class="fab fa-github"></i> GitHub repository
                </a>
                <a href="#quick-start" class="btn btn-secondary">
                    <i class="fas fa-play"></i> Quick start
                </a>
            </div>
        </header>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-number">8.1+</div>
                <div class="stat-label">PHP Version</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">MVC</div>
                <div class="stat-label">Architecture</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">MIT</div>
                <div class="stat-label">License</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">100%</div>
                <div class="stat-label">Custom code</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Framework features</h2>
            <p class="section-subtitle">Everything you need to build modern web applications</p>
            
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <h3>MVC Architecture</h3>
                    <p>Clean separation of concerns with Model-View-Controller pattern for maintainable code.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <h3>Custom Router</h3>
                    <p>Powerful URL routing with dynamic parameters and middleware support.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Security Built-in</h3>
                    <p>CSRF protection, XSS filtering, and SQL injection prevention out of the box.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Query Builder</h3>
                    <p>Database abstraction layer with fluent query builder interface.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h3>Middleware System</h3>
                    <p>Flexible middleware for authentication, CSRF, and custom filters.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Auto Migrations</h3>
                    <p>Database schema versioning with automatic migration execution.</p>
                </div>
            </div>
        </div>

        <div class="architecture">
            <div class="container">
                <h2 class="section-title">Request lifecycle</h2>
                <p class="section-subtitle">Understand how requests flow through your application</p>
                
                <div class="architecture-diagram">
                    <div class="flow-item">HTTP Request</div>
                    <div class="flow-item">Front Controller (index.php)</div>
                    <div class="flow-item">Router & Middleware</div>
                    <div class="flow-item primary">Controller</div>
                    <div class="flow-item">Model & Database</div>
                    <div class="flow-item">View Template</div>
                    <div class="flow-item">HTTP Response</div>
                </div>
            </div>
        </div>

        <div id="quick-start" class="section">
            <h2 class="section-title">Quick start guide</h2>
            <p class="section-subtitle">Get up and running in just a few minutes</p>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Clone repository</h4>
                        <p>Start by cloning the framework to your local development environment</p>
                        <div class="code-block">
                            <code>git clone https://github.com/Inforteuz/codeigniter-alternative.git my-project</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Configure environment</h4>
                        <p>Copy the environment file and configure your application settings</p>
                        <div class="code-block">
                            <code>cp .env.example .env</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Set permissions</h4>
                        <p>Ensure the writable directory has proper permissions</p>
                        <div class="code-block">
                            <code>chmod -R 755 writable/</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Create controller</h4>
                        <p>Build your application logic in controllers</p>
                        <div class="code-block">
                            <code>&lt;?php
namespace App\Controllers;

class HomeController extends BaseController {
    public function index() {
        $data = ['title' => 'Welcome'];
        $this->view('home/index', $data);
    }
}</code>
                        </div>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h4>Define routes</h4>
                        <p>Map URLs to controller methods in the router configuration</p>
                        <div class="code-block">
                            <code>$this->addRoute('GET', '', 'HomeController', 'index');</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <div class="footer-links">
                <a href="https://inforte.uz/codeigniter-alternative/" target="_blank">Documentation</a>
                <a href="https://github.com/Inforteuz/codeigniter-alternative" target="_blank">GitHub</a>
                <a href="https://inforte.uz" target="_blank">Examples</a>
                <a href="https://github.com/Inforteuz/codeigniter-alternative/issues" target="_blank">Support</a>
            </div>
            <p>Built with passion by <a href="https://inforte.uz" target="_blank">Inforte</a></p>
            <p>&copy; <?php echo date("Y"); ?> CodeIgniter Alternative Framework - v2.0.0</p>
            <p>PHP <?php echo PHP_VERSION; ?> • Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
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

        document.querySelectorAll('.feature-card, .step').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
