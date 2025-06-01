<?php
namespace App\Service\Notification\Model;

use PDO;
use App\Database\Database;

class Notification
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection('service_notification');
    }

    public function create(array $data): array
    {
        try {

            if (!isset($data['message']) or $data['message'] == '') {

                throw new \Exception('Create Notification Error');
            }

            $query = 'INSERT INTO notifications (user_id, action, message) 
              VALUES (:user_id, :action, :message)';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':user_id' => (!empty($data['user_id'])?$data['user_id']:NULL),
                ':action' => ($data['action'] != ''?$data['action']:NULL),
                ':message' => $data['message']
            ]);

            $id = $this->pdo->lastInsertId();

            $data['notification_id'] = $id;

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    public function get(int $userId): array
    {
        $query = 'SELECT * 
          FROM notifications 
          WHERE user_id = :user_id 
          ORDER BY date_insert DESC';
        $statement = $this->pdo->prepare($query);
        $statement->execute([':user_id' => $userId]);
        $notification_list = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $notification_list;
    }

//    public function delete(int $id): array
//    {
//        $query = 'DELETE FROM users WHERE id = :id';
//        $statement = $this->pdo->prepare($query);
//        if (!$statement->execute([':id' => $id]))
//        {
//            throw new \Exception("Error while deleting");
//        }
//
//        return ['id' => $id];
//    }
//
//    public function update(int $userId, array $data): array
//    {
//        $query = 'UPDATE users SET username = :username, first_name = :first_name, last_name = :last_name, email = :email, phone = :phone WHERE id = :id';
//        $statement = $this->pdo->prepare($query);
//        $statement->execute([
//            ':id' => $userId,
//            ':username' => $data['username'],
//            ':first_name' => $data['firstName'],
//            ':last_name' => $data['lastName'],
//            ':email' => $data['email'],
//            ':phone' => $data['phone']
//        ]);
//
//        return $data;
//    }
}