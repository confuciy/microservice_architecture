<?php
namespace App\Service\User\Model;

use PDO;
use App\Database\Database;

class User
{
    private $database;
    private $pdo;

    public function __construct()
    {
        $this->database = new Database();
        $this->pdo = $this->database->getConnection('service_user');
    }

    public function getDatabase():Database
    {
        return $this->database;
    }

    # Создание JWT-токена пользователя в сервисе авторизации
    public function createJWTtoken(int $userId): array
    {
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/auth/token');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $userId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
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

            throw new \Exception("Invalid /user/auth JWT-token data");
        }

        return json_decode($content, true);
    }

    # Получение пользователя по почте и паролю
    public function getUserByEmailAndPassword(string $email, string $password): array
    {
        $query = 'SELECT * 
          FROM users 
          WHERE email = :email 
          AND password = :password';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            ':email' => $email,
            ':password' => $password
        ]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception("User not found");
        }

        return $user;
    }

    # Проверка существования пользователя по почте
    public function checkUserExists(string $email): bool
    {
        $query = 'SELECT user_id 
          FROM users 
          WHERE email = :email';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            ':email' => $email
        ]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (isset($user['user_id']) and !empty($user['user_id'])) {

            return true;

        } else {

            return false;
        }
    }

    # Выход пользователя
    public function exit(): void
    {
        // Формируем строку с кукой
        $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/auth/exit');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json-patch+json',
            'Cookie: ' . $cookie_string  // Передаем куку в заголовке
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  // Альтернативный способ передачи куки
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

        if ($content != '') {

            throw new \Exception("Invalid /user/exit");
        }

        return;
    }

    # Создание пользователя
    public function create(array $data): array
    {
        try {

            if (!isset($data['email']) or !isset($data['password'])) {

                throw new \Exception('Create User Error');
            }

            $query = 'INSERT INTO users (username, first_name, last_name, email, phone, password, address)
              VALUES (:username, :first_name, :last_name, :email, :phone, :password, :address)';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':username' => $data['username'],
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':password' => '*'.strtoupper(sha1(sha1($data['password'], true))),
                ':address' => $data['address']
            ]);

            $id = $this->pdo->lastInsertId();

            $data['user_id'] = $id;

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Получение пользователя
    public function get(int $userId): array
    {
        $query = 'SELECT user_id, username, first_name, last_name, email, phone, address 
          FROM users 
          WHERE user_id = :user_id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([':user_id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception("User not found");
        }

        return $user;
    }

    # Обновление пользователя
    public function update(int $userId, array $data): array
    {
        $query = 'UPDATE users
          SET username = :username, first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, address = :address
          WHERE user_id = :user_id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            ':user_id' => $userId,
            ':username' => $data['username'],
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':address' => $data['address']
        ]);

        return $data;
    }

    # Удаление пользователя
    public function delete(int $userId): array
    {
        $query = 'DELETE FROM users 
          WHERE user_id = :user_id';
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute([':user_id' => $userId]))
        {
            throw new \Exception("Error while deleting");
        }

        return ['user_id' => $userId];
    }
}