<?php
namespace App\Model;

use PDO;
use App\Database\Database;

class User
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }

    public function create(array $data): array
    {
        $query = 'INSERT INTO users (username, first_name, last_name, email, phone) VALUES (:username, :first_name, :last_name, :email, :phone)';
        $statement = $this->pdo->prepare($query);

        $statement->execute([
            ':username' => $data['username'],
            ':first_name' => $data['firstName'],
            ':last_name' => $data['lastName'],
            ':email' => $data['email'],
            ':phone' => $data['phone']
        ]);

        $id = $this->pdo->lastInsertId();

        $data['id'] = $id;

        return $data;
    }

    public function get(int $userId): array
    {
        $query = 'SELECT username, first_name, last_name, email, phone FROM users WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([':id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new \Exception("User not found");
        }

        return $user;
    }

    public function delete(int $id): array
    {
        $query = 'DELETE FROM users WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        if (!$statement->execute([':id' => $id]))
        {
            throw new \Exception("Error while deleting");
        }

        return ['id' => $id];
    }

    public function update(int $userId, array $data): array
    {
        $query = 'UPDATE users SET username = :username, first_name = :first_name, last_name = :last_name, email = :email, phone = :phone WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            ':id' => $userId,
            ':username' => $data['username'],
            ':first_name' => $data['firstName'],
            ':last_name' => $data['lastName'],
            ':email' => $data['email'],
            ':phone' => $data['phone']
        ]);

        return $data;
    }
}