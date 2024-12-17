<?php

/*
|--------------------------------------------------------------------------
| Autoloader skripti
|--------------------------------------------------------------------------
| Ushbu fayl CodeIgniter Alternative frameworkining asosiy yuklovchi mexanizmi
| bo'lib, sinflarni avtomatik yuklash funksiyasini amalga oshiradi. Bu esa
| qo'lda sinflarni birma-bir yuklash zaruratini yo'q qiladi.
| 
| Loyihada dastlab sinflar "App" va "System" namespacelariga asoslangan
| holda tartiblangan. Shu sababli autoloader sinflarni belgilangan papkalar
| (app/ va system/) ichidan qidiradi.
|
| Framework: CodeIgniter Alternative v1.0
| Muallif: Oyatillo
| PHP versiya talabi: 8.1.9 yoki undan yuqori
*/

if (version_compare(PHP_VERSION, '8.1.9', '<')) {
    // PHP versiyasi mos kelmasa xatolik chiqaradi va ishni to'xtatadi
    die("Ushbu framework faqat PHP 8.1 yoki undan yuqori versiyada ishlaydi. Sizning PHP versiyangiz: " . PHP_VERSION);
    exit();
}

/*
|--------------------------------------------------------------------------
| Avtomatik yuklash funksiyasi
|--------------------------------------------------------------------------
| Ushbu funksiya namespace asosida sinflar joylashgan faylni avtomatik ravishda
| topib, uni yuklaydi. Sinf "App" yoki "System" namespace’lari bilan boshlanadi.
| 
| 1. Agar sinf "App\Controllers" yoki "App\Models" bo'lsa, tegishli papkadan
|    yuklanadi (masalan, Controllers, Models).
| 2. Agar sinf "System" namespaceiga tegishli bo'lsa, system papkasidan yuklanadi.
| 
| Bu yondashuv dasturda sinflarni yaxshi tartibda tashkil qilishga yordam beradi.
*/

spl_autoload_register(function ($class) {
    // Prefix for "App" namespace
    $appPrefix = "App\\";
    // Prefix for "System" namespace
    $systemPrefix = "System\\";

    // Base directories (app/ and system/)
    $baseDirs = [__DIR__ . "/app/", __DIR__ . "/system/"];

    // If the class is from the "App" namespace
    if (strncmp($appPrefix, $class, strlen($appPrefix)) === 0) {
        // Get the class name after the namespace
        $relativeClass = substr($class, strlen($appPrefix));

        if (strpos($relativeClass, "\\Controllers\\") === 0) {
            // Create the path for the Controller file
            $file = $baseDirs[0] . "Controllers" . str_replace("\\", "/", substr($relativeClass, strlen("Controllers\\"))) . ".php";
        } elseif (strpos($relativeClass, "\\Models\\") === 0) {
            // Create the path for the Model file
            $file = $baseDirs[0] . "Models" . str_replace("\\", "/", substr($relativeClass, strlen("Models\\"))) . ".php";
        } else {
            // General path for other "App" classes
            $file = $baseDirs[0] . str_replace("\\", "/", $relativeClass) . ".php";
        }

        if (file_exists($file)) {
            require $file;
            return;
        }

    // If the class is from the "System" namespace
    } elseif (strncmp($systemPrefix, $class, strlen($systemPrefix)) === 0) {
        $relativeClass = substr($class, strlen($systemPrefix));
        $file = $baseDirs[1] . str_replace("\\", "/", $relativeClass) . ".php";

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

?>