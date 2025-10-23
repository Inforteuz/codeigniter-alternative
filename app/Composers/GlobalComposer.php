<?php
/**
 * --------------------------------------------------------------------------
 * CodeIgniter Alternative Framework
 * --------------------------------------------------------------------------
 * 
 * Global Composer Class
 * 
 * This class is responsible for injecting global view data into layouts,
 * headers, and specific view sections within the CodeIgniter Alternative
 * framework. It provides a clean and organized way to share data such as
 * application configuration, user info, and metadata across multiple views.
 * 
 * @package     CodeIgniterAlternative
 * @subpackage  App\Composers
 * @author      Anvarov Oyatillo
 * @license     MIT License
 * @since       Version 1.0.0
 * --------------------------------------------------------------------------
 */

namespace App\Composers;

class GlobalComposer
{
    /**
     * Compose data for the main layout template.
     *
     * @param \System\BaseController $controller
     * @return void
     */
    public function composeLayout(\System\BaseController $controller)
    {
        $controller->setData([
            'site_name'        => 'My Awesome Code',
            'current_user'     => $controller->getSession('user'),
            'app_version'      => '1.0.0',
            'current_year'     => date('Y'),
            'is_authenticated' => $controller->getSession('logged_in') ?? false,
        ]);
    }

    /**
     * Compose data for the HTML <head> section.
     *
     * @param \System\BaseController $controller
     * @return void
     */
    public function composeHeader(\System\BaseController $controller)
    {
        $controller->setData([
            'page_title'       => $controller->getData('title', 'Welcome'),
            'meta_description' => $controller->getData('description', 'Default description'),
            'csrf_token'       => $controller->getCSRFToken(),
        ]);
    }

    /**
     * Compose data for the admin/user dashboard views.
     *
     * @param \System\BaseController $controller
     * @return void
     */
    public function composeDashboard(\System\BaseController $controller)
    {
        $controller->setData([
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