<?php

namespace App\Core\Database;

use System\Database\Database;

abstract class Seeder
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Call another seeder sequentially.
     *
     * @param string $class
     * @return void
     */
    protected function call(string $class)
    {
        // Try looking in App\Database\Seeds first if namespace isn't fully qualified
        if (strpos($class, '\\') === false) {
            $class = "\\App\Database\\Seeds\\" . $class;
        }

        if (class_exists($class)) {
            $seeder = new $class();
            $seeder->run();
        } else {
            echo "\033[31mSeeder class {$class} not found.\033[0m\n";
        }
    }
}
