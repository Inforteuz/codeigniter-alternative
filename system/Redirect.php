<?php

/**
 * Redirect.php
 *
 * This file contains the Redirect class, which handles URL redirection.
 * It provides simple methods to redirect users to a different location.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    1.0.0
 * @date       2024-12-01
 *
 * Description:
 * The `Redirect` class offers basic functionality for sending HTTP redirects.
 *
 * 1. **Redirecting to a full URL**:
 *    - The `to($url)` method redirects the user to the specified URL.
 *    - The provided URL should be absolute (starting with http:// or https://).
 *
 * @class Redirect
 *
 * @methods
 * - `to($url)`: Sends a redirect to the given URL and stops further execution.
 *
 * @example
 * ```php
 * $redirect = new \System\Redirect();
 * $redirect->to("https://example.com");
 * ```
 */

namespace System;

class Redirect
{
    /**
     * Redirects the user to a given full URL
     *
     * @param string $url The destination URL (should be absolute)
     * @return void
     */
    public function to($url)
    {
        header("Location: {$url}");
        exit();
    }
}
?>