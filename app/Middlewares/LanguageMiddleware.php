<?php

namespace App\Middlewares;

/**
 * Class LanguageMiddleware
 * 
 * Middleware to detect and set the application language.
 * 
 * Language detection priority:
 * 1. URL parameter (e.g., ?lang=uz)
 * 2. Language stored in session
 * 3. Browser's accepted language settings
 * 4. Default language ('uz')
 * 
 * The chosen language is saved in the session and defined as CURRENT_LANGUAGE constant.
 */
class LanguageMiddleware
{
    /**
     * Detects and sets the current language.
     * 
     * @return bool Always returns true.
     */
    public function handle()
    {
        $supportedLanguages = ['uz', 'ru', 'en'];
        $defaultLanguage = 'uz';
        
        // Determine the language based on priority order
        $lang = $_GET['lang'] ?? $_SESSION['app_language'] ?? $this->getBrowserLanguage() ?? $defaultLanguage;
        
        // Validate if the language is supported
        if (!in_array($lang, $supportedLanguages)) {
            $lang = $defaultLanguage;
        }
        
        // Store the language in session
        $_SESSION['app_language'] = $lang;
        
        // Define a constant for the current language if not defined yet
        if (!defined('CURRENT_LANGUAGE')) {
            define('CURRENT_LANGUAGE', $lang);
        }
        
        return true;
    }
    
    /**
     * Get the primary language from the browser's accepted languages.
     * 
     * @return string|null Returns the two-letter language code or null if not available.
     */
    private function getBrowserLanguage()
    {
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (empty($acceptLanguage)) {
            return null;
        }
        
        $languages = explode(',', $acceptLanguage);
        $primaryLanguage = explode(';', $languages[0])[0];
        $primaryLanguage = substr($primaryLanguage, 0, 2);
        
        return $primaryLanguage;
    }
}
?>