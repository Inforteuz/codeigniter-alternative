<?php

/**
 * BaseController.php
 *
 * Ushbu fayl asosiy kontroller sinfini taqdim etadi, u orqali boshqa kontrollerlar yaratish uchun
 * umumiy funksiyalar va asosiy imkoniyatlar taqdim etiladi.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    1.0.0
 * @date       2024-12-01
 * 
 * @description
 * Ushbu sinf quyidagi asosiy funksiyalarni bajaradi:
 *
 * 1. **Session boshqaruvi**:
 *    - Sinf ishga tushganda sessiyani avtomatik boshlaydi.
 * 
 * 2. **Yo'naltirish (Redirect)**:
 *    - `to($url)` - foydalanuvchini berilgan URL manziliga yo'naltiradi.
 *    - `base_url($path)` - saytning asosiy URL manziliga nisbiy yo'lni birlashtiradi.
 * 
 * 3. **Xabarlarni filtr qilish**:
 *    - `filterMessage($message)` - foydalanuvchi kiritgan ma'lumotlarni xavfsiz qilish uchun maxsus belgilarni olib tashlaydi.
 *
 * 4. **Log qilish**:
 *    - `logError($message)` - tizimda yuz bergan xatolarni kunlik log fayllarida saqlaydi.
 *
 * 5. **Ko'rinishni (View) yuklash**:
 *    - `view($view, $data)` - berilgan ko'rinish faylini yuklaydi va unga ma'lumotlarni uzatadi.
 *    - Agar ko'rinish fayli topilmasa, 500 xatolik sahifasini ko'rsatadi.
 *
 * 6. **Xatoliklarni ko'rsatish**:
 *    - `showError($title, $message)` - foydalanuvchi uchun chiroyli xatolik sahifasini ko'rsatadi.
 *    - `show404()` - 404 xatolik (sahifa topilmadi) sahifasini qaytaradi.
 *    - `show500($message)` - 500 xatolik (ichki server xatosi) sahifasini qaytaradi.
 *
 * 7. **Noyob foydalanuvchi identifikatori yaratish**:
 *    - `generateUserId()` - har bir foydalanuvchi uchun noyob va tasodifiy ID generatsiya qiladi.
 *
 * @class BaseController
 * 
 * @methods
 * - `__construct()`: Sinfni ishga tushirish va sessiyani boshlash.
 * - `to($url)`: Foydalanuvchini boshqa URL manzilga yo'naltirish.
 * - `redirect()`: Yo'naltirish uchun tayyor obyektni qaytarish (chainable redirect).
 * - `base_url($path)`: Saytning asosiy URLiga nisbatan yo'l hosil qilish.
 * - `filterMessage($message)`: Kiritilgan ma'lumotni xavfsiz shaklga keltirish.
 * - `logError($message)`: Tizimdagi xatoliklarni log fayliga yozish.
 * - `view($view, $data)`: Ko'rinish faylini yuklash va ma'lumot uzatish.
 * - `showError($title, $message)`: Foydalanuvchi uchun xatolik sahifasini ko'rsatish.
 * - `show404()`: 404 xatolik sahifasini ko'rsatish.
 * - `show500($message)`: 500 xatolik sahifasini ko'rsatish.
 * - `generateUserId()`: Noyob foydalanuvchi ID generatsiya qilish.
 *
 * @properties
 * - `logError()`: Xatoliklarni yozish uchun loglash funksiyasi.
 *
 * Ushbu sinf MVC frameworkingizda barcha kontrollerlar uchun asosiy bo'lib xizmat qiladi.
 */

namespace System;

// require_once 'vendor/autoload.php';

class BaseController
{
    public function __construct()
    {
        session_start();
    }

    public function to($url)
    {
        header("Location: $url");
        exit();
    }

    public function redirect()
    {
        return $this; 
    }

    public function base_url($path = '')
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($path, '/');
    }

    public function filterMessage($message)
    {
        $pattern = '/[\"<>\/*\&\%\$\#\(\)\[\]\{\}]/';

        $cleanedMessage = preg_replace($pattern, '', $message);

        $cleanedMessage = str_replace(["'", '`'], "‘", $cleanedMessage);

        if ($cleanedMessage !== $message) {
            return $cleanedMessage;
        }

        return $message;
    }

    protected function logError($message)
    {
        $logDir = __DIR__ . '/../writable/logs';
        date_default_timezone_set("Asia/Tashkent");
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        $dateTime = date('Y-m-d H:i:s');
        $logMessage = "[{$dateTime}] ERROR: {$message}\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    protected function logDebug($message)
    {
    $logDir = __DIR__ . '/../writable/logs';
    
    date_default_timezone_set("Asia/Tashkent");
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
    
    $dateTime = date('Y-m-d H:i:s');
    $logMessage = "[{$dateTime}] DEBUG: {$message}\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    public function uploadFile($fileInputName, $allowedExtensions = [], $maxFileSize = 10485760)
    {

    if (!isset($_FILES[$fileInputName])) {
        return $this->jsonResponse(['error' => 'No file uploaded.'], 400);
    }

    $file = $_FILES[$fileInputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $this->jsonResponse(['error' => 'Error during file upload.'], 500);
    }

    if ($file['size'] > $maxFileSize) {
        return $this->jsonResponse(['error' => 'File is too large.'], 400);
    }

    if (!empty($allowedExtensions)) {
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        if (!in_array($extension, $allowedExtensions)) {
            return $this->jsonResponse(['error' => 'Invalid file type.'], 400);
        }
    }

    $encryptedFileName = md5(uniqid(rand(), true)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

    $uploadDir = __DIR__ . '/../writable/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $destinationPath = $uploadDir . $encryptedFileName;
    if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
        return $this->jsonResponse(['error' => 'Failed to move uploaded file'], 500);
    }

    return $this->jsonResponse([
        'success' => true,
        'fileName' => $encryptedFileName,
        'filePath' => $destinationPath,
        'originalName' => $file['name'],
        'fileSize' => $file['size']
    ]);
    }

    protected function view($view, $data = [])
    {
        try {
            extract($data);
            $viewFile = "app/Views/{$view}.php";
            if (file_exists($viewFile)) {
                require_once $viewFile;
            } else {
                throw new \Exception("View file \"{$view}.php\" not found.");
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage()); 
            $this->showError("500 Internal Server Error", $e->getMessage());
        }
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
            <h2>{$message}</h2>
            <p>This page does not exist or the request was made incorrectly.</p>
            <a href='/' class='button'>Return to home page</a>
        </div>
    </body>
    </html>
    ";
    }
    /**
     * Debugger: O'zgaruvchilarni chiroyli tarzda ko'rsatish
     *
     * @param mixed $data   - Tekshirilayotgan o'zgaruvchi
     * @param bool $stop    - true bo'lsa skript to'xtaydi (default: true)
     */

    public function dd($data, $stop = true)
    {
        echo "<pre style='background-color: #222; color: #0f0; padding: 15px; border: 1px solid #333; border-radius: 5px; font-family: monospace;'>";
        echo "<strong>Debugging Output:</strong>\n\n";
        print_r($data);
        echo "</pre>";
        
        $this->logDebug(print_r($data, true));
        
        if ($stop) {
            die;
        }
    }

    public function show404()
    {
        header("HTTP/1.1 404 Not Found");
        $this->logError("404 Not Found - The page you are looking for could not be found.");
        $this->view('errors/404');
    }

    public function show500($message)
    {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        $this->logError("500 Internal Server Error - {$message}");
        $this->showError("500 Internal Server Error", $message);
    }

    /**
     * Keshlash (Cache) funksiyasi.
     * 
     * Ushbu funksiya berilgan kalit va qiymatni fayl sifatida keshga saqlaydi.
     * Agar kesh mavjud bo'lsa, uning qiymatini qaytaradi.
     *
     * @param string $key - Keshlash kaliti
     * @param mixed $data - Keshlanadigan ma'lumot
     * @param int $duration - Keshlash vaqti (sekundlarda)
     * @return mixed
     */
    public function cache($key, $data = null, $duration = 3600)
    {
    $cacheDir = __DIR__ . '/../writable/cache/';

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $cacheFile = $cacheDir . md5($key) . '.cache';

    if ($data === null) {
        if (file_exists($cacheFile) && (filemtime($cacheFile) + $duration > time())) {
            return unserialize(file_get_contents($cacheFile));
        }
        return null;
    }

    file_put_contents($cacheFile, serialize($data));
    return true;
    }

    /**
     * Noyob user_id generatsiya qilish.
     * 
     * Har bir foydalanuvchi uchun noyob va tasodifiy ID yaratadi.
     * 
     * @return string
     */
    protected function generateUserId()
    {
        return uniqid('USER-');
    }
    
     /**
     * JSON javob qaytarish.
     */
    public function jsonResponse($data, $status = 200)
    {
        header("Content-Type: application/json");
        http_response_code($status);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * POST ma'lumotlarini olish va xavfsiz qaytarish.
     */
    public function inputPost($key)
    {
        return isset($_POST[$key]) ? htmlspecialchars(trim($_POST[$key])) : null;
    }

    /**
     * GET ma'lumotlarini olish va xavfsiz qaytarish.
     */
    public function inputGet($key)
    {
        return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key])) : null;
    }

    /**
     * Flash xabarlarni o'rnatish.
     */
    public function setFlashMessage($key, $message)
    {
        $_SESSION['flash_messages'][$key] = $message;
    }

    /**
     * Flash xabarlarni ko'rsatish.
     */
    public function getFlashMessage($key)
    {
        if (isset($_SESSION['flash_messages'][$key])) {
            $message = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $message;
        }
        return null;
    }

}
?>