<?php
namespace App\Database;

use PDO;
use App\Config\Config as Config;

class Database
{
    private $pdo;

    public function __construct()
    {
        $config = Config::loadConfig();
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};options=--search_path={$config['schema']}";

        try {

            $this->pdo = new PDO($dsn, $config['user'], $config['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e) {

            throw new \Exception("Database connection error: " . $e->getMessage());
        }
    }
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}