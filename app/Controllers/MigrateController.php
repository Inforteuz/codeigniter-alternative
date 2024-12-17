<?php

namespace App\Controllers;

use App\Models\UserModel;

class MigrateController {
    
    /**
     * The method to run migrations
     * 
     * This method finds all PHP files in the `Database/Migrations/` directory and
     * converts each file to the class name. For each migration class, the `up()` method is called, which
     * performs the necessary changes to the database.
     *
     * @return void
     */
    public function migrate() {

        // Get all migration files from the `Database/Migrations/` directory
        $migrationFiles = glob(__DIR__ . '/../Database/Migrations/*.php');

        // Loop through each migration file and run the migration
        foreach ($migrationFiles as $migrationFile) {

            // Include the file and create an instance of the migration class
            require_once $migrationFile;

            // Extract the file name (e.g. `2004-07-04-180805_create_users_table.php`)
            $fileName = basename($migrationFile, '.php');

            // Remove the date and time part of the file name
            $fileNameWithoutDate = preg_replace('/^\d{4}-\d{2}-\d{2}-\d{6}_/', '', $fileName);

            // Convert the class name to the correct format (e.g. `create_users_table` -> `CreateUsersTable`)
            $className = str_replace('_', '', ucwords($fileNameWithoutDate, '_'));

            // Create an instance of the migration class
            $migration = new $className;

            // Call the `up()` method to perform the migration
            $migration->up();

            // The migration was successful, so print a message to the user
            // echo "Migration was successful: " . $className . "\n";
        }
    }
}
?>