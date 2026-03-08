<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception - <?= htmlspecialchars($error['message'] ?? 'Unknown Error') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f3f4f6;
            --container-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent-red: #ef4444;
            --accent-red-light: #fee2e2;
            --border-color: #e5e7eb;
            --code-bg: #1f2937;
            --code-text: #f3f4f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            line-height: 1.5;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--container-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .header {
            background-color: var(--accent-red-light);
            padding: 2rem;
            border-bottom: 1px solid var(--accent-red);
        }

        .type-badge {
            display: inline-block;
            background-color: var(--accent-red);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--accent-red);
            margin-bottom: 0.5rem;
            word-wrap: break-word;
        }

        .location {
            font-family: 'Fira Code', monospace;
            font-size: 0.875rem;
            color: #7f1d1d;
            background: rgba(255,255,255,0.5);
            padding: 0.5rem;
            border-radius: 6px;
            display: inline-block;
        }

        .body {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .trace-box {
            background: var(--code-bg);
            color: var(--code-text);
            padding: 1.5rem;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Fira Code', monospace;
            font-size: 0.875rem;
            line-height: 1.7;
            white-space: pre-wrap;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        }

        .environment {
            margin-top: 2rem;
            border-top: 1px solid var(--border-color);
            padding-top: 2rem;
        }

        .env-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .env-card {
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.25rem;
        }

        .env-card h3 {
            font-size: 0.875rem;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .env-list {
            list-style: none;
            font-family: 'Fira Code', monospace;
            font-size: 0.8125rem;
        }

        .env-list li {
            display: flex;
            padding: 0.25rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .env-list li:last-child {
            border-bottom: none;
        }

        .env-list .key {
            font-weight: 600;
            color: var(--text-main);
            width: 40%;
            word-break: break-all;
        }

        .env-list .val {
            color: #059669;
            width: 60%;
            word-break: break-all;
            padding-left: 0.5rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="type-badge"><?= htmlspecialchars($error['type'] ?? 'Exception') ?></div>
        <h1 class="title"><?= htmlspecialchars($error['message'] ?? 'Something went wrong') ?></h1>
        
        <?php if (!empty($error['file'])): ?>
        <div class="location">
            <?= htmlspecialchars($error['file']) ?> : <strong><?= htmlspecialchars($error['line']) ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <div class="body">
        <?php if (!empty($error['trace'])): ?>
        <h2 class="section-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
            Stack Trace
        </h2>
        <div class="trace-box"><?= htmlspecialchars($error['trace']) ?></div>
        <?php endif; ?>

        <div class="environment">
            <h2 class="section-title">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Environment & Request
            </h2>
            
            <div class="env-grid">
                <div class="env-card">
                    <h3>Server Variables</h3>
                    <ul class="env-list">
                        <li><span class="key">PHP Version</span><span class="val"><?= phpversion() ?></span></li>
                        <li><span class="key">Request URI</span><span class="val"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'CLI') ?></span></li>
                        <li><span class="key">Method</span><span class="val"><?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'CLI') ?></span></li>
                        <li><span class="key">User Agent</span><span class="val"><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'None') ?></span></li>
                    </ul>
                </div>

                <div class="env-card">
                    <h3>Routing Params</h3>
                    <ul class="env-list">
                        <?php if (!empty($_GET)): ?>
                            <?php foreach ($_GET as $k => $v): ?>
                            <li><span class="key">GET.<?= htmlspecialchars($k) ?></span><span class="val"><?= htmlspecialchars(is_array($v) ? json_encode($v) : $v) ?></span></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><span class="key">$_GET</span><span class="val">Empty</span></li>
                        <?php endif; ?>

                        <?php if (!empty($_POST)): ?>
                            <?php foreach ($_POST as $k => $v): ?>
                            <li><span class="key">POST.<?= htmlspecialchars($k) ?></span><span class="val"><?= htmlspecialchars(is_array($v) ? json_encode($v) : $v) ?></span></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><span class="key">$_POST</span><span class="val">Empty</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
