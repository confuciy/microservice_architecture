<?php
namespace App\Migrations;

use PDO;
use App\Database\Database;

class CreateUsersTable
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }

    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS ".getenv('db_schema').".users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL
        );";
        $this->pdo->exec($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS ".getenv('db_schema').".users";
        $this->pdo->exec($sql);
    }
}