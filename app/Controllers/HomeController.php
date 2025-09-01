<?php

/**
 * HomeController.php
 * 
 * Basic starter controller for handling homepage, login, and logout.
 */

namespace App\Controllers;

use System\BaseController;

class HomeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // You can load models here if needed in the future
    }

    /**
     * Homepage - default route
     */
    public function index()
    {
        $this->view('home/index', [
            'title' => 'Home Page'
        ]);
    }

    /**
     * Login page
     */
    public function login()
    {
        $this->view('auth/login', [
            'title' => 'Login'
        ]);
    }

    /**
     * Logout the user and redirect to login page
     */
    public function logout()
    {
        // Destroy the session
        session_destroy();
        session_start();

        // Redirect to login
        $this->to('/login');
    }
}
?>