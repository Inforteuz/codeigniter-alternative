<?php

/*
|--------------------------------------------------------------------------
| Autoloader script
|--------------------------------------------------------------------------
| This file is the main autoload mechanism of the CodeIgniter Alternative framework,
| implementing the automatic class loading function. This eliminates the need
| to manually include classes one by one.
| 
| In the project, classes are initially organized based on the "App" and "System" namespaces.
| Therefore, the autoloader searches for classes inside the specified folders
| (app/ and system/).
|
| Framework: CodeIgniter Alternative v1.0
| Author: Oyatillo
| PHP version requirement: 8.1.9 or higher
*/

if (version_compare(PHP_VERSION, '8.1.9', '<')) {
    // Throws an error and stops execution if PHP version is incompatible
    die("This framework only works with PHP 8.1 or higher. Your PHP version is: " . PHP_VERSION);
    exit();
}

/*
|--------------------------------------------------------------------------
| Autoload function
|--------------------------------------------------------------------------
| This function automatically finds and loads the file where the class
| is located based on its namespace. Classes start with "App" or "System" namespaces.
| 
| 1. If the class belongs to "App\Controllers" or "App\Models",
|    it is loaded from the corresponding folder (e.g. Controllers, Models).
| 2. If the class belongs to the "System" namespace, it is loaded from the system folder.
| 
| This approach helps to organize classes well in the application.
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
        // Get the class name after the namespace prefix
        $relativeClass = substr($class, strlen($appPrefix));

        if (strpos($relativeClass, "\\Controllers\\") === 0) {
            // Build the path for the Controller file
            $file = $baseDirs[0] . "Controllers" . str_replace("\\", "/", substr($relativeClass, strlen("Controllers\\"))) . ".php";
        } elseif (strpos($relativeClass, "\\Models\\") === 0) {
            // Build the path for the Model file
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