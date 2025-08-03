<?php
namespace App\Service\Delivery\Model;

use PDO;
use App\Database\Database;

class Delivery
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection('service_delivery');
    }

    # Создание резерва курьера
    public function create(array $data): array
    {
        try {

            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('ID заказа пустой');
            }
            if (!isset($data['delivery_id']) or empty($data['delivery_id'])) {
                throw new \Exception('ID курьера пустой');
            }

            $query = 'INSERT INTO delivery_actions (delivery_id, user_id, order_id, action, delivery_date) 
              VALUES (:delivery_id, :user_id, :order_id, :action, :delivery_date)';
            $statement = $this->pdo->prepare($query);
            $statement->execute([
                ':delivery_id' => $data['delivery_id'],
                ':user_id' => $data['user_id'],
                ':order_id' => $data['order_id'],
                ':action' => 'create',
                ':delivery_date' => date('Y-m-d', (time() + (86400 * 2)))
            ]);

            $id = $this->pdo->lastInsertId();

            $data['delivery_action_id'] = $id;

            # Изменяем доступность курьера
            $this->setDeliveryFree($data['delivery_id'], 0);

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Получение курьера на складе
    public function get(int $delivery_id): array
    {
        $query = 'SELECT * 
          FROM delivery 
          WHERE delivery_id = :delivery_id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([':delivery_id' => $delivery_id]);
        $delivery = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$delivery) {
            throw new \Exception("Курьер не найден");
        }

        return $delivery;
    }

    # Удаление резерва курьера
    public function delete(array $data): array
    {
        try {

            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('ID заказа пустой');
            }
            if (!isset($data['delivery_id']) or empty($data['delivery_id'])) {
                throw new \Exception('ID курьера пустой');
            }

            $query = 'INSERT INTO delivery_actions (delivery_id, user_id, order_id, action, delivery_date) 
              VALUES (:delivery_id, :user_id, :order_id, :action, :delivery_date)';
            $statement = $this->pdo->prepare($query);
            $statement->execute([
                ':delivery_id' => $data['delivery_id'],
                ':user_id' => $data['user_id'],
                ':order_id' => $data['order_id'],
                ':action' => 'delete',
                ':delivery_date' => date('Y-m-d', (time() + (86400 * 2)))
            ]);

            $id = $this->pdo->lastInsertId();

            $data['delivery_action_id'] = $id;

            # Изменяем доступность курьера
            $this->setDeliveryFree($data['delivery_id'], 1);

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Получение свободного курьера
    public function getDeliveryFree(): array
    {
        $query = 'SELECT * 
          FROM delivery
          WHERE free = 1 
          LIMIT 1';
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $delivery = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$delivery) {
            throw new \Exception("Курьеры не найдены");
        }

        return $delivery;
    }

    # Получение списка курьеров
    public function getDeliveryList(): array
    {
        $query = 'SELECT * 
          FROM delivery 
          ORDER BY date_insert DESC';
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $delivery = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$delivery) {
            throw new \Exception("Курьеры не найдены");
        }

        return $delivery;
    }

    # Получение зарезервированного курьера заказа
    public function getDeliveryOrder(int $userId, int $orderId): array
    {
        try {

            if (empty($userId)) {
                throw new \Exception('ID пользователя пустой');
            }
            if (empty($orderId)) {
                throw new \Exception('ID заказа пустой');
            }

            $query = 'SELECT delivery_actions.*, delivery.courier, delivery.free
              FROM delivery_actions 
              JOIN delivery ON delivery.delivery_id = delivery_actions.delivery_id
              WHERE delivery_actions.user_id = :user_id 
              AND delivery_actions.order_id = :order_id 
              ORDER BY date_insert DESC 
              LIMIT 1';
            $statement = $this->pdo->prepare($query);
            $statement->execute([':user_id' => $userId, ':order_id' => $orderId]);
            $delivery = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$delivery) {
                throw new \Exception("Заказ не найден");
            }

            return $delivery;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    #  Изменяем доступность курьера
    public function setDeliveryFree(int $delivery_id, int $free) : void
    {
        try {

            if (empty($delivery_id)) {
                throw new \Exception('ID курьера пустой');
            }

            $query = 'UPDATE delivery 
              SET free = :free
              WHERE delivery_id = :delivery_id';
            $statement = $this->pdo->prepare($query);
            $statement->execute(['free' => $free, ':delivery_id' => $delivery_id]);

            return;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }
}