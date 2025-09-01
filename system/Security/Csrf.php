<?php

namespace System\Security;

/**
 * CSRF Class
 *
 * This class provides protection against CSRF (Cross-Site Request Forgery) attacks.
 * It offers functions to generate, verify, and retrieve CSRF tokens.
 * 
 * CSRF tokens are used to validate user requests and help prevent unauthorized
 * or malicious requests from being processed by the system.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System\Security
 * @version    1.0.0
 * @date       2024-12-01
 * 
 * @description
 * Handles creation, verification, and storage of CSRF tokens.
 *
 * 1. **generateToken()**:
 *    - Generates a new CSRF token and stores it in the session.
 *
 * 2. **verifyToken($token)**:
 *    - Compares the incoming token with the one stored in the session.
 *    - If they match, the token is removed from the session and returns `true`.
 *    - Otherwise, it returns `false`.
 *
 * 3. **getToken()**:
 *    - Returns the CSRF token stored in the session, or `null` if none exists.
 *
 * @class Csrf
 *
 * @methods
 * - `generateToken()`: Generates and stores a new CSRF token.
 * - `verifyToken($token)`: Verifies the provided token against the session token.
 * - `getToken()`: Retrieves the current CSRF token from the session.
 *
 * @example
 * ```php
 * // Generate a new token
 * $csrfToken = \System\Security\Csrf::generateToken();
 * 
 * // Verify the token
 * if (\System\Security\Csrf::verifyToken($_POST['csrf_token'])) {
 *     // Token is valid
 * } else {
 *     // Token is invalid
 * }
 * 
 * // Get the token
 * $token = \System\Security\Csrf::getToken();
 * ```
 */

class Csrf {
    /**
     * Generate and store a CSRF token
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate a 64-character (32 bytes) random token
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;

        return $token;
    }

    /**
     * Verify the provided CSRF token against the session token
     */
    public static function verifyToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            // Token matches, remove it from session to prevent reuse
            unset($_SESSION['csrf_token']);
            return true;
        }

        return false;
    }

    /**
     * Retrieve the CSRF token from the session
     */
    public static function getToken(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['csrf_token'] ?? null;
    }
}

?>