<?php
/**
 * Global Composer Class
 */

namespace App\Composers;

use App\Core\View\Engine;

class GlobalComposer
{
    public function composeGlobal(Engine $engine)
    {
        $engine->share([
            'site_name'        => 'CodeIgniter Alternative',
            'app_version'      => '2.0.0',
            'current_year'     => date('Y'),
            'csrf_token'       => $_SESSION['csrf_token'] ?? '',
        ]);
    }

    public function composeDashboard(Engine $engine)
    {
        $engine->share([
            'stats' => [
                'users'    => 0,
                'posts'    => 0,
                'comments' => 0,
            ],
            'recent_activity' => [],
        ]);
    }
}
?>