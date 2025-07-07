<?php
namespace App\Service\Warehouse\Model;

use PDO;
use App\Database\Database;

class Warehouse
{
    private $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection('service_warehouse');
    }

    # Создание резерва товара
    public function create(array $data): array
    {
        try {

            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('ID заказа пустой');
            }
            if (!isset($data['warehouse_list']) or !sizeof($data['warehouse_list'])) {
                throw new \Exception('Список товаров пустой');
            }

            # Записываем кол-во списываемого товара
            $warehouse_count = [];

            foreach ($data['warehouse_list'] as $warehouse_item) {

                $warehouse = $this->get($warehouse_item['warehouse_id']);

                $query = 'INSERT INTO warehouse_actions (user_id, order_id) 
                  VALUES (:user_id, :order_id)';
                $statement = $this->pdo->prepare($query);
                $statement->execute([
                    ':warehouse_id' => $warehouse_item['warehouse_id'],
                    ':order_id' => $data['order_id'],
                    ':action' => 'minus',
                    ':count' => $warehouse_item['count'],
                    ':amount' => $warehouse['amount'],
                    ':status' => 1
                ]);

                $warehouse_count[$warehouse_item['warehouse_id']] = $warehouse['count'];
            }

            # Изменяем кол-во товара на складе
            $this->setWarehouseListCount($warehouse_count, 'minus');

            $data['warehouse_action_list'] = $this->getWarehouseOrderList($data['order_id']);

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Получение товара на складе
    public function get(int $warehouse_id): array
    {
        $query = 'SELECT * 
          FROM warehouse 
          WHERE warehouse_id = :warehouse_id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([':warehouse_id' => $warehouse_id]);
        $warehouse = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$warehouse) {
            throw new \Exception("Товар не найден");
        }

        return $warehouse;
    }

    # Удаление резерва товара
    public function delete(array $data): array
    {
        try {

            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('ID заказа пустой');
            }
            if (!isset($data['warehouse_list']) or !sizeof($data['warehouse_list'])) {
                throw new \Exception('Список товаров пустой');
            }

            # Записываем кол-во списываемого товара
            $warehouse_count = [];

            foreach ($data['warehouse_list'] as $warehouse_item) {

                $warehouse = $this->get($warehouse_item['warehouse_id']);

                $query = 'INSERT INTO warehouse_actions (user_id, order_id) 
                  VALUES (:user_id, :order_id)';
                $statement = $this->pdo->prepare($query);
                $statement->execute([
                    ':warehouse_id' => $warehouse_item['warehouse_id'],
                    ':order_id' => $data['order_id'],
                    ':action' => 'plus',
                    ':count' => $warehouse_item['count'],
                    ':amount' => $warehouse['amount'],
                    ':status' => 1
                ]);

                $warehouse_count[$warehouse_item['warehouse_id']] = $warehouse['count'];
            }

            # Изменяем кол-во товара на складе
            $this->setWarehouseListCount($warehouse_count, 'plus');

            $data['warehouse_action_list'] = $this->getWarehouseOrderList($data['order_id']);

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Получение списка товаров на складе
    public function getWarehouseList(): array
    {
        $query = 'SELECT * 
          FROM warehouse 
          ORDER BY date_insert DESC';
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $warehouse = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$warehouse) {
            throw new \Exception("Товар не найден");
        }

        return $warehouse;
    }

    # Получение зарезервированных товаров по заказу
    public function getWarehouseOrderList(int $order_id): array
    {
        try {

            if (empty($order_id)) {
                throw new \Exception('ID заказа пустой');
            }

            $query = 'SELECT warehouse_actions.*, warehouse.price, warehouse.descr, 
              warehouse.photo, (warehouse.price * warehouse_actions.count) as price_total 
              FROM warehouse_actions 
              JOIN warehouse ON warehouse.warehouse_id = warehouse_actions.warehouse_id
              WHERE warehouse_actions.order_id = :order_id';
            $statement = $this->pdo->prepare($query);
            $statement->execute([':order_id' => $order_id]);
            $warehouse = $statement->fetchAll(PDO::FETCH_ASSOC);

            if (!$warehouse) {
                throw new \Exception("Заказ не найден");
            }

            return $warehouse;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Изменение кол-ва товаров на складе
    public function setWarehouseListCount(array $data, string $action): void
    {
        try {

            foreach ($data as $warehouse_id => $count) {

                $query = 'SELECT count 
                  FROM warehouse 
                  WHERE warehouse_id = :warehouse_id';
                $statement = $this->pdo->prepare($query);
                $statement->execute([':warehouse_id' => $warehouse_id]);
                $warehouse = $statement->fetch(PDO::FETCH_ASSOC);

                $query = 'UPDATE warehouse 
                  SET count = :count
                  WHERE warehouse_id = :warehouse_id';
                $statement = $this->pdo->prepare($query);
                $statement->execute(['count' => ($action == 'minus'?($warehouse['count'] - $count):($warehouse['count'] + $count)), ':warehouse_id' => $warehouse_id]);
            }

            return;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Проверка достаточного кол-ва товара на складе
    public function checkWarehouseListCount(array $data): int
    {
        try {

            if (!isset($data['warehouse_list']) or !sizeof($data['warehouse_list'])) {
                throw new \Exception('Список товаров пустой');
            }

            # Ошибка кол-ва товара
            $error = 0;

            foreach ($data['warehouse_list'] as $warehouse_item) {

                $warehouse = $this->get($warehouse_item['warehouse_id']);

                # Ошибка
                if ($warehouse_item['count'] > $warehouse['count']) {

                    $error = 1;
                }
            }

            return $error;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

//    # Проверка существования пользователя по почте
//    public function checkWarehouseExists(int $userId): bool
//    {
//        $query = 'SELECT warehouse_id
//          FROM warehouse
//          WHERE user_id = :user_id';
//        $statement = $this->pdo->prepare($query);
//        $statement->execute([
//            ':user_id' => $userId
//        ]);
//        $warehouse = $statement->fetch(PDO::FETCH_ASSOC);
//
//        if (isset($warehouse['warehouse_id']) and !empty($warehouse['warehouse_id'])) {
//
//            return true;
//
//        } else {
//
//            return false;
//        }
//    }
//
//    # Создание биллнг-аккаунта
//    public function createAction(array $data): array
//    {
//        try {
//
//            if (!isset($data['warehouse_id']) or empty($data['warehouse_id'])) {
//                throw new \Exception('ID заказа пустой');
//            }
//
//            $query = 'INSERT INTO warehouse_actions (warehouse_id, action, amount)
//              VALUES (:warehouse_id, :action, :amount)';
//            $statement = $this->pdo->prepare($query);
//
//            $statement->execute([
//                ':warehouse_id' => $data['warehouse_id'],
//                ':action' => $data['action'],
//                ':amount' => $data['amount']
//            ]);
//
//            $id = $this->pdo->lastInsertId();
//
//            $data['warehouse_action_id'] = $id;
//
//            return $data;
//
//        } catch (\Exception $e) {
//
//            throw new \Exception($e->getMessage());
//        }
//    }


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