<?php

namespace App\Middlewares;

use System\BaseController;

/**
 * Class CsrfMiddleware
 * 
 * Middleware to protect against Cross-Site Request Forgery (CSRF) attacks.
 * It validates CSRF tokens on all non-GET HTTP requests.
 * 
 * @package App\Middlewares
 */
class CsrfMiddleware extends BaseController
{
    /**
     * Handle the CSRF validation.
     * 
     * For GET requests, it allows the request to proceed without validation.
     * For other request methods, it checks the CSRF token provided in the POST data
     * or in the custom HTTP header 'X-CSRF-TOKEN'.
     * 
     * @return bool Returns true if the CSRF token is valid or the request is GET; false otherwise.
     */
    public function handle()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!$this->validateCsrfToken($token)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate the given CSRF token against the token stored in the session.
     * 
     * @param string $token The CSRF token to validate.
     * @return bool Returns true if tokens match and token is not empty; false otherwise.
     */
    private function validateCsrfToken($token)
    {
        if (empty($token)) {
            return false;
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Handle failure of CSRF validation.
     * 
     * Sends HTTP status 419 (Page Expired) and returns a JSON error message.
     * Then terminates script execution.
     * 
     * @return void
     */
    public function onFailure()
    {
        http_response_code(419);
        echo json_encode([
            'error' => 'CSRF token not valid',
            'message' => 'Iltimos, sahifani yangilang va qayta urunib ko‘ring'
        ]);
        exit();
    }
}
?>