<?php
namespace System\Core;

use App\Models\UserModel;
use System\Core\Session;

/**
 * Class Auth
 *
 * A robust authentication handler for user login, session management,
 * and "remember me" functionality.
 *
 * This class provides a singleton instance that manages:
 * - Secure user login and logout flows.
 * - Persistent "remember me" tokens.
 * - Session regeneration and user state tracking.
 *
 * @package    System\Core
 * @version    2.1.0
 * @author     
 * @since      2025-10-21
 */
class Auth
{
    /**
     * The Session handler instance.
     *
     * @var Session
     */
    protected Session $session;

    /**
     * The user model instance.
     *
     * @var mixed
     */
    protected $userModel;

    /**
     * Cached authenticated user data.
     *
     * @var array|null
     */
    protected ?array $user = null;

    /**
     * Singleton instance of the Auth class.
     *
     * @var static|null
     */
    private static ?self $instance = null;

    /**
     * Auth constructor.
     * 
     * Private to enforce the Singleton design pattern.
     */
    private function __construct()
    {
        $this->session = new Session();
        $this->userModel = new (config('Auth.userModel'))();

        if (!$this->checkSession()) {
            $this->checkRememberMe();
        }
    }

    /**
     * Get the singleton instance of the Auth class.
     *
     * @return static
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Attempt to authenticate a user with the given credentials.
     *
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @param bool  $remember    Whether to enable the "remember me" feature.
     * @return bool Returns true if authentication succeeds, false otherwise.
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        $user = $this->userModel->where('email', $credentials['email'])->first();

        if ($user && password_verify($credentials['password'], $user['password'])) {
            $this->loginUser($user);

            if ($remember) {
                $this->setRememberMe($user['id']);
            }

            return true;
        }

        return false;
    }

    /**
     * Get the currently authenticated user's data.
     *
     * @return array|null
     */
    public function user(): ?array
    {
        if ($this->user === null) {
            $userId = $this->session->get('user_id');
            if ($userId) {
                $this->user = $this->userModel->find($userId);
            }
        }
        return $this->user;
    }

    /**
     * Determine if a user is currently authenticated.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->session->has('user_id');
    }

    /**
     * Log out the authenticated user and clear session data.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->session->remove('user_id');
        $this->clearRememberMe();
        $this->session->regenerate(true);
    }

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->session->get('user_id');
    }

    /**
     * Log in a user and regenerate the session ID to prevent fixation attacks.
     *
     * @param array $user
     * @return void
     */
    private function loginUser(array $user): void
    {
        $this->session->regenerate(true);
        $this->session->set('user_id', $user['id']);
        $this->user = $user;
    }

    /**
     * Set the "remember me" cookie and store the token in the database.
     *
     * @param int $userId
     * @return void
     */
    private function setRememberMe(int $userId): void
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
        $expires = time() + (86400 * 30);

        db()->table(config('Auth.rememberMeTable'))->insert([
            'user_id' => $userId,
            'selector' => $selector,
            'hashed_validator' => $hashedValidator,
            'expires' => date('Y-m-d H:i:s', $expires)
        ]);

        setcookie(
            'remember_me',
            $selector . ':' . $validator,
            $expires,
            '/',
            "",
            false,
            true
        );
    }

    /**
     * Validate the "remember me" cookie and log in the user if valid.
     *
     * @return void
     */
    private function checkRememberMe(): void
    {
        if (empty($_COOKIE['remember_me'])) {
            return;
        }

        list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

        if (!$selector || !$validator) {
            return;
        }

        $token = db()->table(config('Auth.rememberMeTable'))
            ->where('selector', $selector)
            ->first();

        if ($token && password_verify($validator, $token['hashed_validator'])) {
            if (strtotime($token['expires']) > time()) {
                $user = $this->userModel->find($token['user_id']);
                if ($user) {
                    $this->loginUser($user);
                    $this->setRememberMe($user['id']);
                }
            } else {
                $this->clearRememberMeToken($selector);
            }
        }
    }

    /**
     * Clear the "remember me" cookie and remove the token from storage.
     *
     * @return void
     */
    private function clearRememberMe(): void
    {
        if (isset($_COOKIE['remember_me'])) {
            list($selector, ) = explode(':', $_COOKIE['remember_me'], 2);
            $this->clearRememberMeToken($selector);
            setcookie('remember_me', '', time() - 3600, '/');
        }
    }

    /**
     * Remove a "remember me" token from the database.
     *
     * @param string $selector
     * @return void
     */
    private function clearRememberMeToken(string $selector): void
    {
        if (!empty($selector)) {
            db()->table(config('Auth.rememberMeTable'))
                ->where('selector', $selector)
                ->delete();
        }
    }

    /**
     * Check if the session already contains a user ID.
     *
     * @return bool
     */
    private function checkSession(): bool
    {
        return $this->session->has('user_id');
    }
}
