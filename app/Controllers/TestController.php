<?php

namespace App\Controllers;

use System\BaseController;

class TestController extends BaseController
{
    public function index()
    {
        return $this->view('welcome');
    }
}