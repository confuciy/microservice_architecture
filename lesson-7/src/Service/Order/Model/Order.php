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

    public function getOrderList(int $userId): array
    {
        try {

            if (empty($userId)) {
                throw new \Exception('ID пользователя пустой');
            }

            $query = 'SELECT * 
              FROM orders 
              WHERE user_id = :user_id 
              ORDER BY date_insert DESC';
            $statement = $this->pdo->prepare($query);
            $statement->execute([':user_id' => $userId]);
            $order_list = $statement->fetchAll(PDO::FETCH_ASSOC);

            return $order_list;

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

    # Получение биллинг-аккаунта пользователя
    public function getBilling(): array
    {
        // Формируем строку с кукой
        $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/billing');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 0);
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

        return json_decode($content, true);
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