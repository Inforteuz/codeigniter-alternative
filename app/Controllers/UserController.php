<?php

/**
 * UserController.php
 *
 * Bu fayl PHP framework'ida foydalanuvchi (user) bilan bog'liq amallarni bajarish uchun mo'ljallangan.
 * Foydalanuvchi ma'lumotlarini olish, qo'shish, yangilash va o'chirish kabi funksiyalarni o'z ichiga oladi.
 * Ushbu model `users` jadvali bilan bog'liq bo'lib, foydalanuvchi malumotlarini saqlash va qayta ishlashda ishlatiladi.
 *
 * @package    CodeIgniter Alternative
 * @subpackage Models
 * @author     Oyatillo
 * @version    1.0.0
 * @date       2024-12-01
 * */
namespace App\Controllers;

use App\Models\UserModel;
use System\BaseController;
use PDO;

class UserController extends BaseController
{
    public function tasks()
    {

        $id = $_GET['id'] ?? null;
        $user = $_GET['user'] ?? null;  

        if ($id) {
            echo "Task method called with id: " . $id . "<br>";
            echo "Task method called with user: " . $user;
            $this->dd($user);
        } else {
            echo "No ID parameter provided.";
        }
    }
    
}

?>