<?php
/**
 * =========================================================
 * CodeIgniter Alternative Framework - index file
 * =========================================================
 * 
 * Developer: Oyatillo
 * Framework Version: 1.0.0
 * Framework Name: CodeIgniter Alternative
 * 
 * Ushbu fayl framework'ning kirish nuqtasi (entry point) hisoblanadi.
 * Bu yerda barcha kelayotgan so'rovlar marshrutlash tizimi (Router)
 * orqali tegishli controller yoki funksiyaga yo'naltiriladi.
 * 
 * Framework haqida:
 * CodeIgniter Alternative - bu CodeIgniterga muqobil yengil va
 * tezkor PHP MVC framework bo'lib, sizning loyihalaringizni 
 * yanada sodda va tartibli qilish uchun mo'ljallangan.
 * 
 * Barcha huquqlar himoyalangan © Oyatillo, 2024
 * =========================================================
 */

// Autoloader'ni yuklash, bu orqali sinflar avtomatik ravishda yuklanadi
require_once 'autoloader.php';
require_once 'app/Controllers/MigrateController.php';

// 'System' namespacedan Router sinfini chaqirish
use System\Router;

// Migratsiyani ishga tushurish
$migrateController = new \App\Controllers\MigrateController();
$migrateController->migrate();  // Migratsiyani bajaramiz

// Migratsiyani bekor qilish uchun (rollback) $migration->down();

// Router obyektini yaratish va foydalanuvchidan kelgan so'rovga mos ravishda marshrutlashni amalga oshirish
$router = new Router();
$router->handleRequest();
?>