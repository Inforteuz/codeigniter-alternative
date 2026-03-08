<?php

namespace App\Controllers;

use System\BaseController;
use App\Core\Auth\Auth;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        if (Auth::check()) {
            $this->redirect()->to('tasks');
        }
        $this->view('auth/login');
    }

    public function loginAttempt()
    {
        $email = $this->getPost('email');
        $password = $this->getPost('password');

        if (Auth::attempt(['email' => $email, 'password' => $password])) {
            $this->with('success', 'Logged in successfully!')->redirect()->to('tasks');
        }

        $this->flashInput();
        $this->with('error', 'Invalid credentials')->redirect()->to('login');
    }

    public function register()
    {
        if (Auth::check()) {
            $this->redirect()->to('tasks');
        }
        $this->view('auth/register');
    }

    public function registerAttempt()
    {
        // Simple registration logic
        $name = $this->getPost('name');
        $email = $this->getPost('email');
        $password = password_hash($this->getPost('password'), PASSWORD_DEFAULT);

        $userModel = new UserModel();
        // A real app would validate email uniqueness here. Overlooking for simple test app.
        
        $userModel->insertModel([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'user'
        ]);

        Auth::attempt(['email' => $email, 'password' => $this->getPost('password')]);
        
        $this->with('success', 'Registration successful!')->redirect()->to('tasks');
    }
}
