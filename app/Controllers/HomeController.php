<?php

namespace App\Controllers;

use System\BaseController;

/**
 * HomeController
 *
 * This controller handles the default home page request.
 * It extends the BaseController, providing essential features
 * like rendering views and managing application logic.
 *
 * @package App\Controllers
 * @version 1.0.0
 * @author Inforte (Oyatillo)
 * @link https://inforte.uz/codeigniter-alternative/
 */

class HomeController extends BaseController
{
    /**
     * Index Method
     *
     * This method serves as the default entry point for the HomeController.
     * It renders the 'home/index' view, which acts as the default welcome page
     * for the application.
     *
     * @return void
     */
    public function index()
    {
        // Render the default home page view.
        $this->view('home/index');
    }
}

?>