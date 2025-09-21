<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка - Отладочная информация</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            color: #e0e0e0; 
            line-height: 1.6;
            min-height: 100vh;
        }
        .debug-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .debug-header { 
            background: linear-gradient(90deg, #2d2d42 0%, #3d3d5c 100%); 
            padding: 25px; 
            border-radius: 12px; 
            margin-bottom: 25px;
            border-left: 6px solid #e74c3c;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .debug-header h1 { 
            color: #e74c3c; 
            font-size: 28px; 
            margin-bottom: 10px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .debug-header p {
            color: #a0a0c0;
            font-size: 16px;
        }
        .debug-stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 20px; 
            margin-bottom: 25px; 
        }
        .stat-card { 
            background: linear-gradient(135deg, #2d2d42 0%, #3d3d5c 100%); 
            padding: 20px; 
            border-radius: 12px; 
            border-left: 4px solid #3498db; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        .stat-card h3 { 
            color: #3498db; 
            font-size: 14px; 
            margin-bottom: 10px; 
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card .value { 
            font-size: 22px; 
            font-weight: bold; 
            color: #fff;
        }
        .debug-section { 
            background: linear-gradient(135deg, #2d2d42 0%, #3d3d5c 100%); 
            margin-bottom: 25px; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .section-header { 
            background: linear-gradient(90deg, #3d3d5c 0%, #4d4d7c 100%); 
            padding: 18px 20px; 
            cursor: pointer; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            transition: background 0.3s ease;
        }
        .section-header:hover { 
            background: linear-gradient(90deg, #4d4d7c 0%, #5d5d9c 100%); 
        }
        .section-header h2 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-content { 
            padding: 20px; 
            display: none; 
        }
        .section-content.active { display: block; }
        .error-item { 
            background: rgba(231, 76, 60, 0.1); 
            border-left: 4px solid #e74c3c; 
            padding: 18px; 
            margin-bottom: 15px; 
            border-radius: 8px; 
            transition: transform 0.2s ease;
        }
        .error-item:hover {
            transform: translateX(5px);
        }
        .error-type { 
            color: #e74c3c; 
            font-weight: bold; 
            font-size: 14px; 
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .error-message { 
            margin: 8px 0; 
            font-size: 16px; 
            color: #fff;
        }
        .error-location { 
            color: #a0a0c0; 
            font-size: 13px; 
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
        }
        .query-item { 
            background: rgba(39, 174, 96, 0.1); 
            border-left: 4px solid #27ae60; 
            padding: 18px; 
            margin-bottom: 15px; 
            border-radius: 8px; 
            transition: transform 0.2s ease;
        }
        .query-item:hover {
            transform: translateX(5px);
        }
        .query-sql { 
            font-family: 'Fira Code', 'Courier New', monospace; 
            background: rgba(0, 0, 0, 0.3); 
            padding: 12px; 
            border-radius: 6px; 
            margin: 10px 0; 
            overflow-x: auto; 
            font-size: 13px;
            line-height: 1.5;
        }
        .query-params { 
            color: #f39c12; 
            font-size: 13px; 
            margin-top: 8px;
        }
        .query-time { 
            color: #27ae60; 
            font-size: 13px; 
            float: right; 
            background: rgba(39, 174, 96, 0.2);
            padding: 4px 8px;
            border-radius: 4px;
        }
        .system-info { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        .info-card { 
            background: rgba(155, 89, 182, 0.1); 
            padding: 18px; 
            border-radius: 8px; 
            border-left: 4px solid #9b59b6; 
            transition: transform 0.2s ease;
        }
        .info-card:hover {
            transform: translateY(-3px);
        }
        .info-card h3 {
            color: #9b59b6;
            font-size: 15px;
            margin-bottom: 8px;
        }
        .info-card p {
            color: #e0e0e0;
            font-size: 14px;
        }
        .toggle-icon { 
            transition: transform 0.3s; 
            font-size: 14px;
        }
        .toggle-icon.rotated { 
            transform: rotate(180deg); 
        }
        pre { 
            background: rgba(0, 0, 0, 0.3); 
            padding: 12px; 
            border-radius: 6px; 
            overflow-x: auto; 
            font-size: 12px; 
            line-height: 1.4;
            margin-top: 10px;
            font-family: 'Fira Code', 'Courier New', monospace;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
        }
        .badge-error {
            background: #e74c3c;
            color: white;
        }
        .badge-success {
            background: #27ae60;
            color: white;
        }
        .badge-warning {
            background: #f39c12;
            color: white;
        }
        .badge-info {
            background: #3498db;
            color: white;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .debug-stats {
                grid-template-columns: 1fr;
            }
            .system-info {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animatsiyalar */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-content.active {
            animation: fadeIn 0.3s ease;
        }
        
        /* Scrollbar stilizatsiyasi */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #4d4d7c;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #5d5d9c;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <h1><i class="fas fa-bug"></i> Отладочная информация</h1>
            <p>Информация для отладки приложения и метрики производительности</p>
        </div>

        <div class="debug-stats">
            <div class="stat-card">
                <h3>Время выполнения</h3>
                <div class="value"><?= $executionTime ?> мс</div>
            </div>
            <div class="stat-card">
                <h3>Использование памяти</h3>
                <div class="value"><?= $memoryUsage['current'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Пиковое использование памяти</h3>
                <div class="value"><?= $memoryUsage['peak'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Запросы к базе данных</h3>
                <div class="value"><?= count($queries) ?></div>
            </div>
            <div class="stat-card">
                <h3>Ошибки</h3>
                <div class="value" style="color: <?= count($errors) > 0 ? '#e74c3c' : '#27ae60' ?>">
                    <?= count($errors) ?>
                    <?php if (count($errors) > 0): ?>
                        <span class="badge badge-error">Критично</span>
                    <?php else: ?>
                        <span class="badge badge-success">Нет ошибок</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="debug-section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2><i class="fas fa-exclamation-triangle"></i> Ошибки и исключения</h2>
                <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="section-content active">
                <?php foreach ($errors as $index => $error): ?>
                <div class="error-item">
                    <div class="error-type">
                        <i class="fas fa-bug"></i> <?= htmlspecialchars($error['type']) ?>
                        <?php if ($error['type'] === 'Fatal Error'): ?>
                            <span class="badge badge-error">Критично</span>
                        <?php elseif ($error['type'] === 'Exception'): ?>
                            <span class="badge badge-warning">Исключение</span>
                        <?php else: ?>
                            <span class="badge badge-info">Предупреждение</span>
                        <?php endif; ?>
                    </div>
                    <div class="error-message"><?= htmlspecialchars($error['message']) ?></div>
                    <div class="error-location">
                        <i class="fas fa-folder"></i> <?= htmlspecialchars($error['file']) ?> : <?= $error['line'] ?> 
                        <i class="fas fa-clock"></i> <?= $error['time'] ?>
                    </div>
                    <?php if (isset($error['trace'])): ?>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; color: #a0a0c0; font-size: 13px;">
                            <i class="fas fa-list"></i> Показать стек вызовов
                        </summary>
                        <pre><?= htmlspecialchars($error['trace']) ?></pre>
                    </details>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($queries)): ?>
        <div class="debug-section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2><i class="fas fa-database"></i> Запросы к базе данных</h2>
                <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="section-content">
                <?php foreach ($queries as $index => $query): ?>
                <div class="query-item">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>Запрос #<?= $index + 1 ?></strong>
                        <span class="query-time"><?= $query['time'] ?> мс</span>
                    </div>
                    <div class="query-sql"><?= htmlspecialchars($query['sql']) ?></div>
                    <?php if (!empty($query['params'])): ?>
                    <div class="query-params">
                        <i class="fas fa-cog"></i> Параметры: <?= htmlspecialchars(json_encode($query['params'])) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($query['backtrace'])): ?>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; color: #a0a0c0; font-size: 13px;">
                            <i class="fas fa-code-branch"></i> Показать источник вызова
                        </summary>
                        <pre><?= htmlspecialchars(print_r($query['backtrace'], true)) ?></pre>
                    </details>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="debug-section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2><i class="fas fa-cog"></i> Системная информация</h2>
                <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="section-content">
                <div class="system-info">
                    <div class="info-card">
                        <h3><i class="fas fa-code"></i> Версия PHP</h3>
                        <p><?= $systemInfo['php_version'] ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-server"></i> Серверное ПО</h3>
                        <p><?= htmlspecialchars($systemInfo['server_software']) ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-memory"></i> Лимит памяти</h3>
                        <p><?= $systemInfo['memory_limit'] ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-stopwatch"></i> Максимальное время выполнения</h3>
                        <p><?= $systemInfo['max_execution_time'] ?> секунд</p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-upload"></i> Максимальный размер загружаемого файла</h3>
                        <p><?= $systemInfo['upload_max_filesize'] ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-plug"></i> Загруженные расширения</h3>
                        <p><?= count($systemInfo['loaded_extensions']) ?> расширений загружено</p>
                    </div>
                </div>
                
                <?php if (!empty($systemInfo['loaded_extensions'])): ?>
                <details style="margin-top: 20px;">
                    <summary style="cursor: pointer; color: #a0a0c0; font-size: 15px;">
                        <i class="fas fa-list-alt"></i> Показать все расширения
                    </summary>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                        <?php foreach ($systemInfo['loaded_extensions'] as $extension): ?>
                        <span style="background: rgba(52, 152, 219, 0.2); color: #3498db; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                            <?= $extension ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endif; ?>
            </div>
        </div>

        <div class="debug-section">
            <div class="section-header" onclick="toggleSection(this)">
                <h2><i class="fas fa-globe"></i> Информация о запросе</h2>
                <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
            </div>
            <div class="section-content">
                <div class="system-info">
                    <div class="info-card">
                        <h3><i class="fas fa-exchange-alt"></i> Метод запроса</h3>
                        <p><?= $_SERVER['REQUEST_METHOD'] ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-link"></i> URI запроса</h3>
                        <p><?= htmlspecialchars($_SERVER['REQUEST_URI']) ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-user-agent"></i> User Agent</h3>
                        <p><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Неизвестно') ?></p>
                    </div>
                    <div class="info-card">
                        <h3><i class="fas fa-desktop"></i> IP адрес</h3>
                        <p><?= $_SERVER['REMOTE_ADDR'] ?? 'Неизвестно' ?></p>
                    </div>
                </div>
                
                <?php if (!empty($_POST)): ?>
                <h3 style="margin-top: 20px; color: #f39c12; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-paper-plane"></i> POST данные
                </h3>
                <pre><?= htmlspecialchars(json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                <?php endif; ?>
                
                <?php if (!empty($_GET)): ?>
                <h3 style="margin-top: 20px; color: #f39c12; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-search"></i> GET параметры
                </h3>
                <pre><?= htmlspecialchars(json_encode($_GET, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                <?php endif; ?>
                
                <?php if (!empty($_SESSION)): ?>
                <h3 style="margin-top: 20px; color: #9b59b6; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-id-badge"></i> Сессия
                </h3>
                <pre><?= htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSection(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            
            if (content.classList.contains('active')) {
                content.classList.remove('active');
                icon.classList.remove('rotated');
            } else {
                content.classList.add('active');
                icon.classList.add('rotated');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const allHeaders = document.querySelectorAll('.section-header');
            allHeaders.forEach(header => {
                if (!header.nextElementSibling.classList.contains('active')) {
                    header.click();
                }
            });
        
            const errorSection = document.querySelector('.error-item');
            if (errorSection) {
                const errorHeader = document.querySelector('.debug-section:first-child .section-header');
                if (errorHeader) {
                    errorHeader.click();
                }
            }
        });
    </script>
</body>
</html>
