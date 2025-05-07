<?php
namespace App\Database;

use PDO;
use App\Config\Config as Config;

class Database
{
    private $config;

    public function __construct()
    {
        $this->config = [
            'host' => getenv('POSTGRE_HOST'),
            'port' => getenv('POSTGRE_PORT'),
            'dbname' => getenv('POSTGRES_DB'),
            'user' => getenv('POSTGRES_USER'),
            'password' => base64_decode(getenv('POSTGRES_PASSWORD'))
        ];
    }
    public function getConnection(string $schema = ''): PDO
    {
        if ($schema == '') {

            throw new \Exception("Database connection error: SCHEMA is empty!");
        }

        try {

            $dsn = "pgsql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['dbname']};options=--search_path={$schema}";

            $pdo = new PDO($dsn, $this->config['user'], $this->config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $pdo;

        } catch (\PDOException $e) {

            throw new \Exception("Database connection error: " . $e->getMessage());
        }
    }

    # Доабвление оповещения
    public function setNotification(int $userId, string $action, string $message): array
    {
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/notification');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        #curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['login' => $this->login, 'passwordHash' => hash('sha512', $this->password)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $userId, 'action' => $action, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $content = curl_exec($ch);

        if ($content == '') {

            http_response_code(401);
            echo json_encode(['error' => 'Invalid /notification data!']);
        }

        return json_decode($content, true);
    }
}