<?php
namespace App\Service\Auth\Model;

use PDO;
use App\Database\Database;

class Auth
{
    private $database;
    private $pdo;

    public function __construct()
    {
        $this->database = new Database();
        $this->pdo = $this->database->getConnection('service_auth');
    }

    public function getDatabase():Database
    {
        return $this->database;
    }

    public function create(int $userId, string $jwt, int $exp): int
    {
        try {

            if (empty($userId) or $jwt == '' or empty($exp)) {

                throw new \Exception('Create JWT-token Error');
            }

            # Деактивируем текущие JWT-токены пользователя
            $this->deactivate($userId);

            $query = 'INSERT INTO jwt_tokens (user_id, jwt_token, date_expire) 
              VALUES (:user_id, :jwt_token, :date_expire)';
            $statement = $this->pdo->prepare($query);
            $statement->execute([
                ':user_id' => $userId,
                ':jwt_token' => $jwt,
                ':date_expire' => date('Y-m-d H:i:s', $exp)
            ]);

            $id = $this->pdo->lastInsertId();

            return $id;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Деактивация текущих JWT-токенов пользователя
    public function deactivate(int $userId): void
    {
        try {

            if (empty($userId)) {

                throw new \Exception('Create JWT-token Error');
            }

            $query = 'UPDATE jwt_tokens 
              SET status = 0 
              WHERE user_id = :user_id';
            $statement = $this->pdo->prepare($query);
            $statement->execute([':user_id' => $userId]);

            return;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    public function validate(int $userId, string $jwt): array
    {
        $query = 'SELECT jwt_token_id 
          FROM jwt_tokens 
          WHERE user_id = :user_id 
          AND jwt_token = :jwt_token 
          AND date_expire >= :date_expire
          AND status = 1';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            ':user_id' => $userId,
            ':jwt_token' => $jwt,
            ':date_expire' => date('Y-m-d H:i:s', time())
        ]);
        $jwt_token = $statement->fetch(PDO::FETCH_ASSOC);

        return (is_array($jwt_token)?$jwt_token:[]);
    }

    public function data(string $jwt): array
    {
        $query = 'SELECT * 
          FROM jwt_tokens 
          WHERE jwt_token = :jwt_token 
          AND date_expire >= :date_expire
          AND status = 1';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            ':jwt_token' => $jwt,
            ':date_expire' => date('Y-m-d H:i:s', time())
        ]);
        $jwt_token = $statement->fetch(PDO::FETCH_ASSOC);

        return $jwt_token;
    }
}