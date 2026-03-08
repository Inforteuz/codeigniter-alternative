<?php

namespace System\Core;

use System\Core\Env;

/**
 * Session Class
 *
 * Object-oriented wrapper for PHP's native session handling.
 * Uses Env::get() to read configuration values from the .env file.
 *
 * Provides secure session initialization, easy data manipulation,
 * and protection against common session-related vulnerabilities.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System\Core
 * @version    1.1.0
 * @author     
 */
class Session
{
    /**
     * Constructor automatically starts a session if not already started.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $this->start();
        }
    }

    /**
     * Starts a new session with settings from the .env file.
     *
     * @return void
     */
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        if (!Env::isLoaded()) {
            Env::load();
        }

        $sessionName = Env::get('SESSION_NAME', 'ci_alt_session');
        $lifetime = (int) Env::get('SESSION_LIFETIME', 120) * 60; 
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_name($sessionName);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,  
            'httponly' => true,     
            'samesite' => 'Lax',
        ]);

        session_start();

        if (!isset($_SESSION['_last_activity'])) {
            $_SESSION['_last_activity'] = time();
        } elseif (time() - $_SESSION['_last_activity'] > $lifetime) {
            $this->destroy();
            session_start();
        }
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Store a value in the session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a specific session key.
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Completely destroy the current session.
     *
     * @return void
     */
    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Regenerate session ID to prevent fixation attacks.
     *
     * @param bool $destroy Whether to delete old session data
     * @return void
     */
    public function regenerate(bool $destroy = false): void
    {
        session_regenerate_id($destroy);
    }

    /**
     * Returns the current session ID.
     *
     * @return string
     */
    public function id(): string
    {
        return session_id();
    }

    /**
     * Returns all session data.
     *
     * @return array
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }
}
