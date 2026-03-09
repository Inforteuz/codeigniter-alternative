<?php

namespace App\Core\Auth;

use App\Models\UserModel;

class AuthManager
{
    protected $user = null;

    public function attempt(array $credentials): bool
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (!$email || !$password) {
            return false;
        }

        $userModel = new UserModel();
        $user = $userModel->where(['email' => $email])->first();

        if ($user && password_verify($password, $user['password'])) {
            $this->login($user);
            return true;
        }

        return false;
    }

    public function login($user)
    {
        // Prevent session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Set session
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_time'] = time();
        $this->user = $user;
    }

    public function logout()
    {
        unset($_SESSION['logged_in']);
        unset($_SESSION['user_id']);
        unset($_SESSION['login_time']);
        $this->user = null;
        session_regenerate_id(true);
    }

    public function check(): bool
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Check session timeout (8 hours default from old AuthMiddleware)
            $sessionLifetime = 8 * 60 * 60;
            if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $sessionLifetime)) {
                $this->logout();
                return false;
            }
            return true;
        }
        return false;
    }

    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->check() && isset($_SESSION['user_id'])) {
            $userModel = new UserModel();
            $this->user = $userModel->find($_SESSION['user_id']);
            return $this->user;
        }

        return null;
    }

    public function id()
    {
        if ($this->check()) {
            return $_SESSION['user_id'];
        }
        return null;
    }
}
