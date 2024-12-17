<?php

/**
 * Router.php
 *
 * Ushbu fayl URL'larni boshqarish va HTTP so'rovlarini tegishli controller va metodlarga
 * yo'naltirish uchun mas'ul. Router sinfi MVC frameworkingizdagi asosiy qism hisoblanadi.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    1.0.0
 * @date       2024-12-01
 *
 * @description
 * Router sinfi quyidagi asosiy vazifalarni bajaradi:
 *
 * 1. **URL'ni tahlil qilish va marshrutlash (Routing)**:
 *    - So'rov URL'ini tahlil qilib, mos controller va metodlarni aniqlaydi.
 *    - Agar controller yoki metod mavjud bo'lmasa, xatolikni qaytaradi (404).
 *
 * 2. **Controller va metodlarni chaqirish**:
 *    - Mos controller sinfini chaqiradi va aniqlangan metodni ishga tushiradi.
 *    - Qo'shimcha parametrlarni metodga uzatadi.
 *
 * 3. **Xatolikni boshqarish**:
 *    - showError($code, $message) orqali foydalanuvchiga xatolik sahifasini ko'rsatadi.
 *    - logError($message) orqali barcha xatoliklarni log fayliga yozadi.
 *
 * @class Router
 *
 * @methods
 * - handleRequest(): HTTP so'rovini tahlil qilish, controller va metodlarni chaqirish.
 * - logError($message): Xatolikni log fayliga yozish.
 * - showError($code, $message): Foydalanuvchiga xatolik sahifasini ko'rsatish va logga yozish.
 *
 * @example
 * 
php
 * $router = new \System\Router();
 * $router->handleRequest();
 *

 */

namespace System;

/**
 * Router sinfi HTTPS so'rovlarini boshqaradi.
 * URL'ni tekshirish, controller va metodlarni aniqlash va ularga mos keluvchi
 * controllerlarni chaqirish uchun ishlatiladi. Xatolik yuzaga kelsa, foydalanuvchiga
 * xatolik sahifasini ko'rsatadi va logga yozadi.
 */
class Router
{
    /**
     * HTTPS so'rovini qayta ishlaydi.
     * URL'ni analiz qilib, controller va metodlarni aniqlaydi,
     * keyin controller va metodni chaqiradi.
     */
    public function handleRequest()
    {
    // URLni olish va undan query stringni ajratib olish
    $url = $_SERVER["REQUEST_URI"];
    
    // URLni va query stringni ajratish
    $urlParts = explode('?', $url);
    $url = $urlParts[0];  // URL qismi
    parse_str($urlParts[1] ?? '', $_GET); 
    
    // URL'ni trim qilish
    $url = trim($url, "/");

    // Agar URL bo'sh bo'lsa, home/index ga o'tish
    $url = $url ? $url : "home/index";

    // URLni segmentlarga ajratish (controller, method va parametrlar)
    $segments = explode("/", $url);

    // Controller nomini olish
    $controller = ucfirst($segments[0]) . "Controller";

    // Method nomini olish (agar mavjud bo'lmasa, "index" bo'ladi)
    $method = $segments[1] ?? "index";

    // Qo'shimcha parametrlarni olish (agar mavjud bo'lsa)
    $params = array_slice($segments, 2);

    // Controllerni to'liq nomi
    $controllerClass = "App\\Controllers\\" . $controller;

    // Controller faylini tekshirish
    $controllerFile = "app/Controllers/{$controller}.php";

    if (!file_exists($controllerFile)) {
        $this->showError(404, "Controller file '{$controllerFile}' does not exist.");
        return;
    }

    require_once $controllerFile;

    // Agar controller klassi mavjud bo'lsa
    if (class_exists($controllerClass)) {
        $controllerObj = new $controllerClass();

        // Agar metod mavjud bo'lsa, uni parametrlar bilan chaqirish
        if (method_exists($controllerObj, $method)) {
            call_user_func_array([$controllerObj, $method], $params);
        } else {
            $this->showError(404, "Method '{$method}' does not exist.");
        }
    } else {
        $this->showError(404, "Controller class '{$controllerClass}' does not exist.");
    }
    }

    /**
     * Xatoliklarni log faylga yozadi va foydalanuvchiga xatolik sahifasini ko'rsatadi.
     *
     * @param string $message Xatolik xabari
     */
    protected function logError($message)
    {
        // Determine the log file storage location
        $logDir = __DIR__ . '/../writable/logs';
        // Create the log directory if it does not exist
        date_default_timezone_set("Asia/Tashkent");
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Create the log file
        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        // Get the current time
        $dateTime = date('Y-m-d H:i:s');
        // Format the error message
        $logMessage = "[{$dateTime}] ERROR: {$message}\n";
        
        // Write the error to the file
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    /**
     * Xatolikni ko'rsatadi va logga yozadi.
     * 
     * @param int $code Xatolik kodi (masalan, 404)
     * @param string $message Xatolik haqida batafsil ma'lumot
     */
    private function showError($code, $message)
    {
    http_response_code($code);

    $errorFile = __DIR__ . "/../app/Views/errors/{$code}.php";
    
    if (file_exists($errorFile)) {
        include($errorFile);
        
        $this->logError("{$code} {$message}");
        return;
    }

    $this->logError("{$code} {$message}");
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <link rel='icon' href='favicon.ico' type='image/png'>
        <title>{$code} - Xatolik</title>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                color: #333;
            }

            .error-container {
                background-color: #fff;
                padding: 30px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 420px;
                width: 100%;
            }

            .error-container h1 {
                font-size: 60px;
                color: #e74c3c;
                margin-bottom: 10px;
            }

            .error-container h2 {
                font-size: 20px;
                color: #555;
                margin-bottom: 15px;
            }

            .error-container p {
                font-size: 14px;
                color: #777;
                margin-bottom: 20px;
            }

            .error-container .button {
                text-decoration: none;
                background-color: #3498db;
                color: #fff;
                font-size: 14px;
                padding: 8px 18px;
                border-radius: 5px;
                display: inline-block;
                transition: background-color 0.3s ease;
            }

            .error-container .button:hover {
                background-color: #2980b9;
            }

            .error-container .icon {
                font-size: 60px;
                color: #e74c3c;
                margin-bottom: 15px;
            }

            @media (max-width: 600px) {
                .error-container h1 {
                    font-size: 50px;
                }

                .error-container h2 {
                    font-size: 18px;
                }

                .error-container p {
                    font-size: 12px;
                }

                .error-container .button {
                    padding: 6px 12px;
                    font-size: 12px;
                }

                .error-container .icon {
                    font-size: 50px;
                }
            }

        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='icon'>
                <i class='fas fa-exclamation-triangle'></i>
            </div>
            <h1>{$code}</h1>
            <h2>Sorry, the request could not be processed.</h2>
            <p>This page does not exist or the request was made incorrectly.</p>
            <a href='/' class='button'>Return to home page</a>
        </div>
    </body>
    </html>
    ";
}
}
?>