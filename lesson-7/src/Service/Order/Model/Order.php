<?php
namespace App\Service\Order\Model;

use PDO;
use App\Database\Database;

class Order
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection('service_order');
    }

    public function create(array $data = []): array
    {
        try {

            if (!isset($data['user_id']) or empty($data['user_id'])) {
                throw new \Exception('ID пользователя пустой');
            }
            if (!isset($data['amount']) or empty($data['amount'])) {
                throw new \Exception('Пустая сумма');
            }
            if (!isset($data['idempotency']) or $data['idempotency'] == '') {
                throw new \Exception('Хеш идемпотентности пустой');
            }

            $query = 'INSERT INTO orders (user_id, amount, idempotency) 
              VALUES (:user_id, :amount, :idempotency)';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':user_id' => $data['user_id'],
                ':amount' => (!empty($data['amount'])?$data['amount']:NULL),
                ':idempotency' => ($data['idempotency'] != ''?$data['idempotency']:NULL)
            ]);

            $id = $this->pdo->lastInsertId();

            $data['order_id'] = $id;

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    public function get(int $orderId, int $userId): array
    {
        try {

            if (empty($orderId)) {
                throw new \Exception('ID заказа пустой');
            }
            if (empty($userId)) {
                throw new \Exception('ID пользователя пустой');
            }

            $query = 'SELECT * 
              FROM orders 
              WHERE order_id = :order_id 
              AND user_id = :user_id 
              ORDER BY date_insert DESC';
            $statement = $this->pdo->prepare($query);
            $statement->execute([':order_id' => $orderId]);
            $statement->execute([':user_id' => $userId]);
            $order = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception("Заказ не найден");
            }

            return $order;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
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