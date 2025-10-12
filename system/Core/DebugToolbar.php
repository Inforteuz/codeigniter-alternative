<?php
namespace System\Core;

class DebugToolbar
{
    private static $startTime;
    private static $queries = [];
    private static $routes = [];
    private static $logs = [];
    private static $memoryStart;
    private static $enabled = false;
    
    public static function init()
    {
        self::$enabled = Env::get('DEBUG_MODE') === 'true';
        
        if (!self::$enabled) {
            return;
        }
        
        self::$startTime = microtime(true);
        self::$memoryStart = memory_get_usage();
        
        self::log('Framework initialized', 'system');
    }
    
    public static function addQuery($sql, $params = [], $time = 0)
    {
        if (!self::$enabled) return;

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        self::$queries[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time,
            'backtrace' => $backtrace
        ];
        
        self::log("Query executed: " . substr($sql, 0, 100) . (strlen($sql) > 100 ? '...' : ''), 'database');
    }
    
    public static function setRoute($method, $uri, $controller, $action, $middlewares = [])
    {
        if (!self::$enabled) return;

        self::$routes = [
            'method' => $method,
            'uri' => $uri,
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $middlewares
        ];
        
        self::log("Route matched: {$method} {$uri} -> {$controller}::{$action}", 'router');
    }
    
    public static function log($message, $type = 'info')
    {
        if (!self::$enabled) return;
        
        self::$logs[] = [
            'message' => $message,
            'type' => $type,
            'time' => microtime(true),
            'memory' => memory_get_usage()
        ];
    }
    
    public static function render()
    {
        if (!self::$enabled) {
            return '';
        }

        $executionTime = round((microtime(true) - self::$startTime) * 1000, 2);
        $memoryUsage = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $memoryCurrent = round(memory_get_usage() / 1024 / 1024, 2);
        $queryCount = count(self::$queries);
        $totalQueryTime = array_sum(array_column(self::$queries, 'time'));
        $logCount = count(self::$logs);

        ob_start();
        ?>
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>#ci-debug-toolbar{position:fixed;bottom:0;left:0;right:0;background:#1e1e1e;color:#e0e0e0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;font-size:13px;z-index:10000;box-shadow:0 -2px 20px rgba(0,0,0,0.5);border-top:2px solid #dd4814;max-height:95vh}#ci-debug-toolbar-toggle{background:#dd4814;color:white;padding:10px 20px;border:0;cursor:pointer;width:100%;text-align:left;font-weight:600;display:flex;justify-content:space-between;align-items:center;font-size:14px;transition:background .2s;position:sticky;top:0;z-index:10001}#ci-debug-toolbar-toggle:hover{background:#bf3c10}.ci-toolbar-content{display:none;max-height:calc(95vh - 50px);overflow-y:auto;background:#2d2d2d}.ci-toolbar-content.active{display:block}.ci-toolbar-tabs{display:flex;background:#252525;border-bottom:1px solid #444;flex-wrap:wrap;position:sticky;top:0;z-index:10000}.ci-toolbar-tab{padding:12px 20px;cursor:pointer;border-right:1px solid #444;transition:all .2s;font-size:12px;font-weight:500;display:flex;align-items:center;gap:6px;flex:1;min-width:120px;justify-content:center}.ci-toolbar-tab:hover{background:#333}.ci-toolbar-tab.active{background:#dd4814;color:white}.ci-toolbar-panel{display:none;padding:20px;max-height:calc(95vh - 100px);overflow-y:auto}.ci-toolbar-panel.active{display:block}.ci-info-row{display:flex;padding:8px 0;border-bottom:1px solid #3a3a3a;align-items:flex-start;gap:10px}.ci-info-label{color:#aaa;min-width:180px;font-weight:500;flex-shrink:0}.ci-info-value{color:#fff;flex:1;word-break:break-word;overflow-wrap:break-word}.ci-query{background:#252525;padding:12px;margin:8px 0;border-radius:4px;border-left:4px solid #dd4814;word-break:break-word}.ci-query-sql{color:#4ec9b0;margin-bottom:6px;font-family:'Consolas',monospace;font-size:12px;line-height:1.4;white-space:pre-wrap}.ci-query-time{color:#ce9178;font-size:11px;font-weight:500}.ci-badge{background:#dd4814;color:white;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;margin-left:8px}.ci-log-entry{padding:6px 0;border-bottom:1px solid #3a3a3a;font-family:'Consolas',monospace;font-size:12px;word-break:break-word}.ci-log-info{color:#4ec9b0}.ci-log-warning{color:#ffd700}.ci-log-error{color:#f44747}.ci-log-system{color:#569cd6}.ci-log-router{color:#c586c0}.ci-log-database{color:#9cdcfe}.ci-middleware-tag{background:#555;color:white;padding:2px 6px;border-radius:4px;font-size:10px;margin:2px;display:inline-block}.ci-performance-bar{background:#333;height:6px;border-radius:3px;margin:5px 0;overflow:hidden;flex:1;max-width:200px}.ci-performance-fill{background:#dd4814;height:100%;transition:width .3s}.ci-backtrace{background:#1a1a1a;padding:8px;margin-top:8px;border-radius:4px;font-size:10px;color:#888;max-height:150px;overflow-y:auto}.ci-backtrace-item{margin:2px 0;font-family:'Consolas',monospace}.ci-toolbar-content::-webkit-scrollbar{width:8px}.ci-toolbar-content::-webkit-scrollbar-track{background:#2d2d2d}.ci-toolbar-content::-webkit-scrollbar-thumb{background:#555;border-radius:4px}.ci-toolbar-content::-webkit-scrollbar-thumb:hover{background:#777}@media(max-width:768px){.ci-toolbar-tab{padding:10px 12px;min-width:100px;font-size:11px}.ci-info-row{flex-direction:column;gap:5px}.ci-info-label{min-width:auto}.ci-toolbar-panel{padding:15px}}@media(max-width:480px){.ci-toolbar-tabs{flex-direction:column}.ci-toolbar-tab{border-right:0;border-bottom:1px solid #444;justify-content:flex-start}#ci-debug-toolbar-toggle span{font-size:12px}.ci-badge{margin-left:4px;padding:1px 6px;font-size:10px}}</style><div id="ci-debug-toolbar"><button id="ci-debug-toolbar-toggle" onclick="toggleDebugToolbar()"><span><i class="fas fa-bug"></i> CodeIgniter Alternative  <span class="ci-badge"><?= $executionTime ?>ms</span><span class="ci-badge"><?= $memoryUsage ?>MB</span><span class="ci-badge"><?= $queryCount ?> queries</span><span class="ci-badge"><?= $logCount ?> logs</span></span><span id="toolbar-arrow">▼</span></button><div class="ci-toolbar-content" id="ci-toolbar-content"><div class="ci-toolbar-tabs"><div class="ci-toolbar-tab active" onclick="switchTab('overview')"><i class="fas fa-tachometer-alt"></i> Overview  </div><div class="ci-toolbar-tab" onclick="switchTab('routes')"><i class="fas fa-route"></i> Routes  </div><div class="ci-toolbar-tab" onclick="switchTab('database')"><i class="fas fa-database"></i> Database (<?= $queryCount ?>)  </div><div class="ci-toolbar-tab" onclick="switchTab('logs')"><i class="fas fa-list"></i> Logs (<?= $logCount ?>)  </div><div class="ci-toolbar-tab" onclick="switchTab('request')"><i class="fas fa-paper-plane"></i> Request  </div><div class="ci-toolbar-tab" onclick="switchTab('server')"><i class="fas fa-server"></i> Server  </div></div><div class="ci-toolbar-panel active" id="panel-overview"><div class="ci-info-row"><div class="ci-info-label">Execution time:</div><div class="ci-info-value"><div style="display: flex; align-items: center; gap: 10px;"><span><?= $executionTime ?> ms</span><div class="ci-performance-bar"><div class="ci-performance-fill" style="width: <?= min($executionTime / 10, 100) ?>%"></div></div></div></div></div><div class="ci-info-row"><div class="ci-info-label">Memory usage:</div><div class="ci-info-value"><div style="display: flex; align-items: center; gap: 10px;"><span><?= $memoryCurrent ?> MB (Peak: <?= $memoryUsage ?> MB)</span><div class="ci-performance-bar"><div class="ci-performance-fill" style="width: <?= min($memoryCurrent / 10, 100) ?>%"></div></div></div></div></div><div class="ci-info-row"><div class="ci-info-label">Database queries:</div><div class="ci-info-value"><div style="display: flex; align-items: center; gap: 10px;"><span><?= $queryCount ?> queries (Total time: <?= round($totalQueryTime, 2) ?>ms)</span><div class="ci-performance-bar"><div class="ci-performance-fill" style="width: <?= min($totalQueryTime / 5, 100) ?>%"></div></div></div></div></div><div class="ci-info-row"><div class="ci-info-label">PHP version:</div><div class="ci-info-value"><?= PHP_VERSION ?></div></div><div class="ci-info-row"><div class="ci-info-label">Framework version:</div><div class="ci-info-value">2.0.0</div></div><div class="ci-info-row"><div class="ci-info-label">Environment:</div><div class="ci-info-value"><span style="color: <?= Env::get('APP_ENV') === 'production' ? '#f44747' : '#4ec9b0' ?>"><?= Env::get('APP_ENV', 'development') ?></span></div></div><div class="ci-info-row"><div class="ci-info-label">Debug mode:</div><div class="ci-info-value"><span style="color: <?= Env::get('DEBUG_MODE') === 'true' ? '#4ec9b0' : '#f44747' ?>"><?= Env::get('DEBUG_MODE') === 'true' ? 'Enabled' : 'Disabled' ?></span></div></div></div><div class="ci-toolbar-panel" id="panel-routes"><div class="ci-info-row"><div class="ci-info-label">HTTP Method:</div><div class="ci-info-value"><span style="color: <?= (self::$routes['method'] ?? '') === 'GET' ? '#4ec9b0' : ((self::$routes['method'] ?? '') === 'POST' ? '#ffd700' : ((self::$routes['method'] ?? '') === 'PUT' ? '#c586c0' : ((self::$routes['method'] ?? '') === 'DELETE' ? '#f44747' : '#569cd6'))) ?>"><?= self::$routes['method'] ?? 'N/A' ?></span></div></div><div class="ci-info-row"><div class="ci-info-label">Request URI:</div><div class="ci-info-value"><?= htmlspecialchars(self::$routes['uri'] ?? $_SERVER['REQUEST_URI'] ?? 'N/A') ?></div></div><div class="ci-info-row"><div class="ci-info-label">Controller:</div><div class="ci-info-value"><?= self::$routes['controller'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Action/Method:</div><div class="ci-info-value"><?= self::$routes['action'] ?? 'N/A' ?></div></div><?php if (!empty(self::$routes['middlewares'])): ?><div class="ci-info-row"><div class="ci-info-label">Middlewares:</div><div class="ci-info-value"><?php foreach (self::$routes['middlewares'] as $middleware): ?><span class="ci-middleware-tag"><?= $middleware ?></span><?php endforeach; ?></div></div><?php endif; ?></div><div class="ci-toolbar-panel" id="panel-database"><?php if (empty(self::$queries)): ?><p style="color: #888; text-align: center; padding: 20px;">No database queries executed.</p><?php else: ?><?php foreach (self::$queries as $index => $query): ?><div class="ci-query"><div style="color: #fff; font-weight: 600; margin-bottom: 8px;">  Query #<?= $index + 1 ?><span style="color: #ce9178; font-size: 11px; margin-left: 10px;"><?= round($query['time'], 2) ?>ms  </span></div><div class="ci-query-sql"><?= htmlspecialchars($query['sql']) ?></div><?php if (!empty($query['params'])): ?><div style="color: #dcdcaa; font-size: 11px; margin-top: 8px;"><strong>Parameters:</strong><?= htmlspecialchars(json_encode($query['params'], JSON_PRETTY_PRINT)) ?></div><?php endif; ?><?php if (!empty($query['backtrace'])): ?><details class="ci-backtrace"><summary style="cursor: pointer; color: #888; font-size: 10px; margin-top: 8px;">  Backtrace (<?= count($query['backtrace']) ?> frames)  </summary><?php foreach (array_slice($query['backtrace'], 1) as $frame): ?><div class="ci-backtrace-item"><?= htmlspecialchars(($frame['file'] ?? 'unknown') . ':' . ($frame['line'] ?? '0')) ?><?= isset($frame['class']) ? htmlspecialchars($frame['class'] . $frame['type'] . $frame['function']) : htmlspecialchars($frame['function'] ?? 'unknown') ?></div><?php endforeach; ?></details><?php endif; ?></div><?php endforeach; ?><?php endif; ?></div><div class="ci-toolbar-panel" id="panel-logs"><?php if (empty(self::$logs)): ?> <p style="color: #888; text-align: center; padding: 20px;">No logs recorded.</p><?php else: ?><?php foreach (self::$logs as $log): ?><div class="ci-log-entry ci-log-<?= $log['type'] ?>"><span style="color: #6a9955;">[<?= date('H:i:s', (int)$log['time']) ?>.<?= sprintf('%03d', ($log['time'] - floor($log['time'])) * 1000) ?>]</span> <strong><?= strtoupper($log['type']) ?>:</strong><?= htmlspecialchars($log['message']) ?><span style="color: #888; font-size: 10px; margin-left: 10px;">  (+<?= round(($log['time'] - self::$startTime) * 1000, 2) ?>ms)  </span><span style="color: #666; font-size: 9px; margin-left: 5px;">  [<?= round($log['memory'] / 1024 / 1024, 2) ?>MB]  </span></div><?php endforeach; ?><?php endif; ?></div><div class="ci-toolbar-panel" id="panel-request"><div class="ci-info-row"><div class="ci-info-label">Request Method:</div><div class="ci-info-value"><?= $_SERVER['REQUEST_METHOD'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Request URI:</div><div class="ci-info-value"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') ?></div></div><div class="ci-info-row"><div class="ci-info-label">Query String:</div><div class="ci-info-value"><?= $_SERVER['QUERY_STRING'] ? htmlspecialchars($_SERVER['QUERY_STRING']) : 'None' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Remote address:</div><div class="ci-info-value"><?= $_SERVER['REMOTE_ADDR'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">User Agent:</div><div class="ci-info-value" style="font-size: 11px;"><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') ?></div></div><div class="ci-info-row"><div class="ci-info-label">Content Type:</div><div class="ci-info-value"><?= $_SERVER['CONTENT_TYPE'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Accept language:</div><div class="ci-info-value"><?= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Request time:</div><div class="ci-info-value"><?= date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ?? time()) ?></div></div></div><div class="ci-toolbar-panel" id="panel-server"><div class="ci-info-row"><div class="ci-info-label">Server Software:</div><div class="ci-info-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Server Name:</div><div class="ci-info-value"><?= $_SERVER['SERVER_NAME'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Server Address:</div><div class="ci-info-value"><?= $_SERVER['SERVER_ADDR'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Server Port:</div><div class="ci-info-value"><?= $_SERVER['SERVER_PORT'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">Document Root:</div><div class="ci-info-value"><?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' ?></div></div><div class="ci-info-row"><div class="ci-info-label">PHP version:</div><div class="ci-info-value"><?= PHP_VERSION ?></div></div><div class="ci-info-row"><div class="ci-info-label">PHP SAPI:</div><div class="ci-info-value"><?= PHP_SAPI ?></div></div><div class="ci-info-row"><div class="ci-info-label">Max execution time:</div><div class="ci-info-value"><?= ini_get('max_execution_time') ?>s</div></div><div class="ci-info-row"><div class="ci-info-label">Memory limit:</div><div class="ci-info-value"><?= ini_get('memory_limit') ?></div></div><div class="ci-info-row"><div class="ci-info-label">Post max size:</div><div class="ci-info-value"><?= ini_get('post_max_size') ?></div></div><div class="ci-info-row"><div class="ci-info-label">Upload Max Filesize:</div><div class="ci-info-value"><?= ini_get('upload_max_filesize') ?></div></div><div class="ci-info-row"><div class="ci-info-label">Timezone:</div><div class="ci-info-value"><?= date_default_timezone_get() ?></div></div></div></div></div><script>function toggleDebugToolbar(){const content=document.getElementById('ci-toolbar-content');const arrow=document.getElementById('toolbar-arrow');const isActive=content.classList.contains('active');content.classList.toggle('active');arrow.textContent=isActive?'▼':'▲';localStorage.setItem('ci-debug-toolbar-open',!isActive);} function switchTab(tabName){document.querySelectorAll('.ci-toolbar-tab').forEach(tab=>tab.classList.remove('active'));document.querySelectorAll('.ci-toolbar-panel').forEach(panel=>panel.classList.remove('active'));const clickedTab=event.target.closest('.ci-toolbar-tab');if(clickedTab){clickedTab.classList.add('active');} const panel=document.getElementById('panel-'+tabName);if(panel){panel.classList.add('active');} localStorage.setItem('ci-debug-toolbar-tab',tabName);} document.addEventListener('DOMContentLoaded',function(){const isOpen=localStorage.getItem('ci-debug-toolbar-open')==='true';const savedTab=localStorage.getItem('ci-debug-toolbar-tab')||'overview';if(isOpen){document.getElementById('ci-toolbar-content').classList.add('active');document.getElementById('toolbar-arrow').textContent='▲';} document.querySelectorAll('.ci-toolbar-tab').forEach(tab=>tab.classList.remove('active'));document.querySelectorAll('.ci-toolbar-panel').forEach(panel=>panel.classList.remove('active'));const targetTab=document.querySelector(`.ci-toolbar-tab[onclick="switchTab('${savedTab}')"]`);const targetPanel=document.getElementById('panel-'+savedTab);if(targetTab)targetTab.classList.add('active');if(targetPanel)targetPanel.classList.add('active');});document.addEventListener('click',function(event){const toolbar=document.getElementById('ci-debug-toolbar');const toggle=document.getElementById('ci-debug-toolbar-toggle');if(!toolbar.contains(event.target)&&!toggle.contains(event.target)){const content=document.getElementById('ci-toolbar-content');if(content.classList.contains('active')){content.classList.remove('active');document.getElementById('toolbar-arrow').textContent='▼';localStorage.setItem('ci-debug-toolbar-open','false');}}});</script>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if debug toolbar is enabled
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }

    /**
     * Get all collected data for external use
     */
    public static function getData()
    {
        return [
            'queries' => self::$queries,
            'routes' => self::$routes,
            'logs' => self::$logs,
            'memory_peak' => memory_get_peak_usage(),
            'memory_current' => memory_get_usage(),
            'execution_time' => microtime(true) - self::$startTime
        ];
    }
}

?>
