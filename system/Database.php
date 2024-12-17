<?php

/**
 * Database.php
 *
 * Ushbu fayl ma'lumotlar bazasi bilan ishlash uchun zarur bo'lgan sinfni taqdim etadi.
 * `PDO` (PHP Data Objects) asosida ishlovchi ushbu sinf, ma'lumotlar bazasiga ulanish va so'rovlarni
 * bajarish uchun qulay interfeysni ta'minlaydi.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @author     Oyatillo
 * @version    1.0.0
 * @date       2024-12-01
 *
 * @description
 * Ushbu sinf quyidagi asosiy funksiyalarni bajaradi:
 *
 * 1. Ma'lumotlar bazasiga ulanish:
 *    - PDO yordamida `mysql` drayveridan foydalanib, ma'lumotlar bazasiga ulanishni amalga oshiradi.
 *    - Ulanishda xatolik yuz berganda, foydalanuvchiga mos xabar qaytaradi va xatolikni log qiladi.
 *
 * 2. So'rovlarni tayyorlash va bajarish:
 *    - `prepare()` metodi orqali so'rovlarni tayyorlaydi va parametrlarni qo'shish imkoniyatini beradi.
 *    - `fetch()` va `fetchAll()` metodlari orqali yagona yoki bir nechta natijalarni qaytaradi.
 *
 * 3. Xatolarni log qilish:
 *    - Sinf xatoliklarni kuzatib borish uchun loglash funksiyasini taqdim etadi.
 *    - Xatoliklar `writable/logs` papkasiga kunlik formatda yoziladi.
 *    - Har bir log yozuvida vaqt va xato haqida batafsil ma'lumot ko'rsatiladi.
 *
 * @class Database
 * 
 * @properties
 * - `$host`: Ma'lumotlar bazasi serverining manzili (standart: `localhost`).
 * - `$dbName`: Ma'lumotlar bazasining nomi (standart: `phd`).
 * - `$username`: Ma'lumotlar bazasiga ulanish uchun foydalanuvchi nomi (standart: `root`).
 * - `$password`: Ma'lumotlar bazasiga ulanish uchun parol (standart: bo'sh).
 * - `$conn`: PDO ulanish obyekti.
 * 
 * @methods
 * - `__construct()`: Sinf yaratilganda ma'lumotlar bazasiga ulanishni amalga oshiradi.
 * - `connect()`: Ma'lumotlar bazasiga ulanishni amalga oshiradi va ulanish xatolarini boshqaradi.
 * - `logError($message)`: Xatoliklarni log qilish uchun ishlatiladi.
 * - `prepare($sql, $params = [])`: SQL so'rovini tayyorlaydi va bajarishga tayyor holatga keltiradi.
 * - `fetch($sql, $params = [])`: Bitta yozuvni qaytaruvchi SQL so'rovini bajaradi.
 * - `fetchAll($sql, $params = [])`: Bir nechta yozuvlarni qaytaruvchi SQL so'rovini bajaradi.
 *
 * Ushbu sinf ma'lumotlar bazasiga samarali ulanish va xavfsiz ishlash uchun qulay vosita taqdim etadi.
 */

namespace System;

use PDO;

class Database
{
    private $host = 'localhost'; 
    private $dbName = 'phd';
    private $username = 'root'; 
    private $password = ''; 
    private $conn;

    public function __construct()
    {
        $this->connect();
    }

    public function connect()
    {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbName}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->logError("Database connection failed: " . $e->getMessage());
            throw new \Exception("Bazaga ulanishda xatolik yuz berdi. Iltimos, administrator bilan bog'laning.");
        }
    }

    public function logError($message)
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
    
    public function getConnection()
    {
        return $this->conn;
    }

    public function prepare($sql, $params = [])
    {
        try {
            return $this->conn->prepare($sql);
        } catch (PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage());
            throw new \Exception("So'rov bajarishda xatolik yuz berdi.");
        }
    }

    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>