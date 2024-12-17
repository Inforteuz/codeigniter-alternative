<?php

/**
 * Redirect.php
 *
 * Ushbu fayl URL'ga yo'naltirishni boshqarish uchun ishlatiladi. `Redirect` sinfi
 * foydalanuvchini boshqa sahifaga yo'naltirish uchun kerakli metodlarni taqdim etadi.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    1.0.0
 * @date       2024-12-01
 *
 * @description
 * `Redirect` sinfi URL'ga yo'naltirishni amalga oshiradi.
 *
 * 1. **To'liq URL'ga yo'naltirish**:
 *    - `to($url)` metodi orqali foydalanuvchini to'liq URL'ga yo'naltiradi.
 *    - URL sifatida to'liq manzil (http:// yoki https://) va yo'nalishni kiritish kerak.
 *
 * @class Redirect
 *
 * @methods
 * - `to($url)`: Foydalanuvchini ko'rsatilgan URL'ga yo'naltiradi va dasturni to'xtatadi.
 *
 * @example
 * ```php
 * $redirect = new \System\Redirect();
 * $redirect->to("http://example.com");
 * ```
 */

namespace System;

class Redirect
{
    /**
     * To'liq URL'ga yo'naltirish
     */
    
    public function to($url)
    {
        header("Location: {$url}");
        exit();
    }
}
?>