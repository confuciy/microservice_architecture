<?php
require __DIR__ . '/../../vendor/autoload.php';

use App\Migrations\CreateUsersTable;

try {
    $migration = new CreateUsersTable();
    $migration->up();
} catch (Exception $e) {
    echo "Error during migrations: " . $e->getMessage();
}