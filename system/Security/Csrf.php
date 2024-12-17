<?php

namespace System\Security;

/**
 * CSRF sinfi
 *
 * Ushbu sinf CSRF (Cross-Site Request Forgery) hujumlariga qarshi himoya qilish uchun
 * ishlatiladi. CSRF tokenlarini yaratish, tekshirish va saqlash funksiyalari taqdim etiladi.
 * 
 * CSRF token foydalanuvchi so'rovlarini tasdiqlash uchun ishlatiladi va tizimga noma'lum,
 * shubhali so'rovlarni oldini olishga yordam beradi.
 *
 * @package    CodeIgniter Altenarnative
 * @subpackage System\Security
 * @version    1.0.0
 * @date       2024-12-01
 * 
 * @description
 * CSRF token yaratish, tekshirish va saqlashni amalga oshiruvchi funksiyalar.
 *
 * 1. **generateToken()**:
 *    - Yangi CSRF tokenini yaratadi va uni sessiyada saqlaydi.
 *
 * 2. **verifyToken($token)**:
 *    - Kiruvchi tokenni sessiyadagi token bilan solishtirib tekshiradi va agar ular mos
 *      kelsa, tokenni sessiyadan olib tashlaydi va `true` qaytaradi. Aks holda `false` qaytaradi.
 *
 * 3. **getToken()**:
 *    - Sessiyadagi CSRF tokenni qaytaradi. Agar mavjud bo'lsa, tokenni, aks holda `null` qaytaradi.
 *
 * @class Csrf
 *
 * @methods
 * - `generateToken()`: Yangi CSRF tokenini yaratadi va sessiyada saqlaydi.
 * - `verifyToken($token)`: Kiruvchi tokenni sessiyadagi token bilan taqqoslaydi.
 * - `getToken()`: Sessiyadagi CSRF tokenni qaytaradi.
 *
 * @example
 * ```php
 * // Yangi token yaratish
 * $csrfToken = \System\Security\Csrf::generateToken();
 * 
 * // Tokenni tekshirish
 * if (\System\Security\Csrf::verifyToken($_POST['csrf_token'])) {
 *     // Token to'g'ri
 * } else {
 *     // Token noto'g'ri
 * }
 * 
 * // Tokenni olish
 * $token = \System\Security\Csrf::getToken();
 * ```
 */

class Csrf {
    /**
     * CSRF token yaratish va saqlash
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32)); 
        $_SESSION['csrf_token'] = $token; 

        return $token;
    }

    /**
     * CSRF tokenni tekshirish
     */
    public static function verifyToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            unset($_SESSION['csrf_token']);
            return true;
        }

        return false;
    }

    /**
     * CSRF tokenni olish
     */
    public static function getToken(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['csrf_token'] ?? null;
    }
}

?>