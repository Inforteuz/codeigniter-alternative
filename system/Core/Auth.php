<?php
namespace System\Core;

/**
 * Auth Class
 *
 * This class handles user authentication processes including login attempts,
 * session management, and logout functionality.
 * It interacts with the UserModel to verify user credentials and manages
 * user session data upon successful authentication.
 * 
 * @package    CodeIgniter Alternative
 * @subpackage System\Core
 * @version    1.0.0
 * @date       2024-12-01
 *
 * @description
 * Provides methods to:
 *  - Attempt user login by verifying credentials against stored data.
 *  - Retrieve the currently authenticated user's data from the session.
 *  - Check if a user is currently authenticated.
 *  - Log out the user by clearing session data.
 *  - Get the authenticated user's ID.
 *
 * @methods
 * - `attempt($credentials)`: Attempts to authenticate a user with given credentials.
 * - `user()`: Returns the currently authenticated user's information.
 * - `check()`: Checks if a user is logged in.
 * - `logout()`: Logs out the current user and destroys the session.
 * - `id()`: Retrieves the ID of the currently authenticated user.
 *
 * @example
 * $auth = new \System\Core\Auth();
 * if ($auth->attempt(['email' => $email, 'password' => $password])) {
 *     // Login successful
 * } else {
 *     // Login failed
 * }
 */

class Auth
{
    public function attempt($credentials)
    {
        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('email', $credentials['email'])->first();
        
        if ($user && password_verify($credentials['password'], $user['password'])) {
            $_SESSION['user'] = $user;
            return true;
        }
        
        return false;
    }

    public function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public function check()
    {
        return isset($_SESSION['user']);
    }

    public function logout()
    {
        unset($_SESSION['user']);
        session_destroy();
    }

    public function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }
}
?>