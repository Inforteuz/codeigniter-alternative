<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="generator" content="CodeIgniter">
    <meta name="description" content="404 - Page Not Found - CodeIgniter Alternative Framework">
    <link rel="icon" href="favicon.ico" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>404 - Page Not Found | CodeIgniter Alternative</title>
    <style>
        :root {
            --ci-primary: #dd4814;
            --ci-primary-dark: #bf3c10;
            --ci-secondary: #6c757d;
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
            background: linear-gradient(135deg, var(--ci-light) 0%, var(--ci-bg) 100%);
            color: var(--ci-text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            max-width: 600px;
            width: 100%;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--ci-primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(221, 72, 20, 0.3);
            animation: bounce 2s infinite;
        }

        .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--ci-primary);
            letter-spacing: -0.025em;
        }

        .error-content {
            background: var(--ci-bg);
            padding: 60px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--ci-border);
            margin-bottom: 30px;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            color: var(--ci-primary);
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 4px 4px 0px rgba(221, 72, 20, 0.1);
            animation: pulse 2s infinite;
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--ci-dark);
            margin-bottom: 16px;
        }

        .error-message {
            font-size: 1.125rem;
            color: var(--ci-text-muted);
            margin-bottom: 40px;
            line-height: 1.7;
        }

        .error-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
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
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(221, 72, 20, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: var(--ci-text);
            border-color: var(--ci-border);
        }

        .btn-secondary:hover {
            background: var(--ci-light);
            border-color: var(--ci-primary);
            transform: translateY(-2px);
        }

        .error-details {
            background: var(--ci-bg);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--ci-border);
            margin-top: 30px;
            text-align: left;
        }

        .error-details summary {
            font-weight: 600;
            color: var(--ci-primary);
            cursor: pointer;
            padding: 8px 0;
        }

        .error-details pre {
            background: var(--ci-light);
            padding: 16px;
            border-radius: 6px;
            margin-top: 12px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: var(--ci-text);
            overflow-x: auto;
            border: 1px solid var(--ci-border);
        }

        .footer {
            text-align: center;
            color: var(--ci-text-muted);
            margin-top: 40px;
            font-size: 0.9rem;
        }

        .footer a {
            color: var(--ci-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .logo-text {
                font-size: 2rem;
            }

            .logo-icon {
                width: 56px;
                height: 56px;
                font-size: 24px;
            }

            .error-content {
                padding: 40px 24px;
            }

            .error-code {
                font-size: 6rem;
            }

            .error-title {
                font-size: 1.75rem;
            }

            .error-message {
                font-size: 1rem;
            }

            .error-actions {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 1.75rem;
            }

            .logo-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            body {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-fire"></i>
            </div>
            <span class="logo-text">CodeIgniter Alternative</span>
        </div>

        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-message">
                The page you're looking for doesn't exist or has been moved. 
                Please check the URL or navigate back to the homepage.
            </p>
            
            <div class="error-actions">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go Home
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>

            <?php if (isset($_SERVER['APP_DEBUG']) && $_SERVER['APP_DEBUG']): ?>
            <details class="error-details">
                <summary>Technical Details</summary>
                <pre>Request URI: <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Unknown') ?>
Request method: <?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'Unknown') ?>
Script name: <?= htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'Unknown') ?>
PHP version: <?= PHP_VERSION ?>
Framework: CodeIgniter Alternative v2.0.0</pre>
            </details>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date("Y"); ?> CodeIgniter Alternative Framework - v2.0.0</p>
            <p>Built with passion by <a href="https://inforte.uz" target="_blank">Inforte</a></p>
            <p>PHP <?php echo PHP_VERSION; ?> • Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const errorCode = document.querySelector('.error-code');
            
            errorCode.addEventListener('click', function() {
                this.style.animation = 'none';
                setTimeout(() => {
                    this.style.animation = 'pulse 2s infinite';
                }, 10);
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.history.back();
                } else if (e.key === 'Home' || e.key === 'h') {
                    window.location.href = '/';
                }
            });

            console.log('%c🔍 404 - Page Not Found', 'color: #dd4814; font-size: 16px; font-weight: bold;');
            console.log('%cThe requested URL was not found on this server.', 'color: #6c757d;');
            console.log('%cCheck the URL for typos or navigate to the homepage.', 'color: #6c757d;');
        });
    </script>
</body>
</html>