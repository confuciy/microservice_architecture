<?php
namespace App\Service\Order\Model;

use PDO;
use App\Database\Database;

class Order
{
    public $pdo;

    public function __construct()
    {
        $database = new Database();
        $this->pdo = $database->getConnection('service_order');
    }

    # Создание заказа
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

//            # Резервируем товар
//            $data['warehouse_list'] = $this->setWarehouseOrderList($data);

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Создание SAGA заказа
    public function createSaga(array $data = []): array
    {
        try {

            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('ID заказа пустой');
            }
            if (!isset($data['amount']) or empty($data['amount'])) {
                throw new \Exception('Пустая сумма');
            }

            $query = 'INSERT INTO orders_saga (order_id, amount) 
              VALUES (:order_id, :amount)';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':order_id' => $data['order_id'],
                ':amount' => (!empty($data['amount'])?$data['amount']:NULL)
            ]);

            $id = $this->pdo->lastInsertId();

            $data['order_saga_id'] = $id;

            return $data;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Обновление поля заказа
    public function updateOrder(int $order_id, string $field, $value): void
    {
        try {

            if (empty($order_id)) {
                throw new \Exception('ID заказа пустой');
            }
            if ($field == '') {
                throw new \Exception('Наименование поля пустое');
            }

            $query = 'UPDATE orders 
              SET '.$field.' = :value 
              WHERE order_id = :order_id';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':value' => $value,
                ':order_id' => $order_id
            ]);

            return;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Обновление поля SAGA
    public function updateSaga(int $order_saga_id, string $field, $value): void
    {
        try {

            if (empty($order_saga_id)) {
                throw new \Exception('ID SAGA заказа пустой');
            }
            if ($field == '') {
                throw new \Exception('Наименование поля пустое');
            }

            $query = 'UPDATE orders_saga 
              SET '.$field.' = :value 
              WHERE order_saga_id = :order_saga_id';
            $statement = $this->pdo->prepare($query);

            $statement->execute([
                ':value' => $value,
                ':order_saga_id' => $order_saga_id
            ]);

            return;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Получение SAGA для начала обработки
    public function getSagaForStartList(): array
    {
        try {

            $query = 'SELECT orders.user_id, orders_saga.* 
              FROM orders_saga 
              JOIN orders ON orders.order_id = orders_saga.order_id
              WHERE orders_saga.status = 0';
            $statement = $this->pdo->prepare($query);
            $statement->execute();
            $order_saga_list = $statement->fetchAll(PDO::FETCH_ASSOC);

            return (is_array($order_saga_list)?$order_saga_list:[]);

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

//    # Резервирование товаров заказа
//    public function setWarehouseOrderList(array $data = []): array
//    {
//        try {
//
//            if (!isset($data['user_id']) or empty($data['user_id'])) {
//                throw new \Exception('ID пользователя пустой');
//            }
//            if (!isset($data['order_id']) or empty($data['order_id'])) {
//                throw new \Exception('ID заказа пустой');
//            }
//
//            // Формируем строку с кукой
//            $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);
//
//            $ch = curl_init();
//            $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
//            curl_setopt($ch, CURLOPT_URL, getenv('host').'/warehouse');
//            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
//            curl_setopt($ch, CURLOPT_POST, 1);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['order_id' => $data['order_id'], 'warehouse_list' => $data['warehouse_list']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
//            curl_setopt($ch, CURLOPT_HTTPHEADER, [
//                'Content-Type: application/json-patch+json',
//                'Cookie: ' . $cookie_string  // Передаем куку в заголовке
//            ]);
//            curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  // Альтернативный способ передачи куки
//            curl_setopt($ch, CURLOPT_NOBODY, 0);
//            curl_setopt($ch, CURLOPT_HEADER, 0);
//            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
//            #curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
//            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
//            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
//            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
//            $content = curl_exec($ch);
//
//            $content_arr = json_decode($content, true);
//
//            if (!isset($content_arr['warehouse_list']) or !sizeof($content_arr['warehouse_list'])) {
//                throw new \Exception('Список товаров не зарегистрирован');
//            }
//
//            return $content_arr['warehouse_list'];
//
//        } catch (\Exception $e) {
//
//            throw new \Exception($e->getMessage());
//        }
//    }

    # Получение списка заказов пользователя
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
            $order_list_tmp = $statement->fetchAll(PDO::FETCH_ASSOC);

            $order_list = [];

            if (is_array($order_list_tmp) and sizeof($order_list_tmp) > 0) {

                foreach ($order_list_tmp as $order) {

                    # Если товар зарезервирован
                    if ($order['status'] >= 3) {

                        $order['warehouse_action_list'] = $this->getWarehouseOrderList($order['order_id']);
                    }

                    # Если товар ожидает доставку
                    if ($order['status'] >= 4) {

                        $order['delivery_action_list'] = $this->getDeliveryOrderList($order['order_id']);
                    }

                    $order_list[] = $order;
                }
            }

            return $order_list;

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
    }

    # Получение заказа пользователя
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
              AND user_id = :user_id';
            $statement = $this->pdo->prepare($query);
            $statement->execute([':order_id' => $orderId, ':user_id' => $userId]);
            $order = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new \Exception("Заказ не найден");
            }

            # Если товар зарезервирован
            if ($order['status'] >= 3) {

                $order['warehouse_action_list'] = $this->getWarehouseOrderList($order['order_id']);
            }

            # Если товар ожидает доставку
            if ($order['status'] >= 4) {

                $order['delivery_action_list'] = $this->getDeliveryOrderList($order['order_id']);
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

    # Действие с суммой биллинг аккаунта
    public function billingAmount(string $action, float $amount): array
    {
        if ($action == '') {
            throw new \Exception('Не указано действие');
        }
        if ($amount <= 0) {
            throw new \Exception('Сумма не может быть отрицательной или равной нулю');
        }

        // Формируем строку с кукой
        $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/billing/amount');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => $action, 'amount' => $amount], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json-patch+json',
            'Cookie: ' . $cookie_string  // Передаем куку в заголовке
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  // Альтернативный способ передачи куки
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        #curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $content = curl_exec($ch);

        return json_decode($content, true);
    }

    # Получение зарезервированных товаров заказа пользователя
    public function getWarehouseOrderList(int $order_id): array
    {
        if (empty($order_id)) {
            throw new \Exception('ID заказа для получения товаров заказа не задан');
        }

        // Формируем строку с кукой
        $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/warehouse/order');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['order_id' => $order_id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json-patch+json',
            'Cookie: ' . $cookie_string  // Передаем куку в заголовке
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  // Альтернативный способ передачи куки
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        #curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $content = curl_exec($ch);

        return json_decode($content, true);
    }

    # Получение зарезервированных товаров заказа пользователя
    public function getDeliveryOrderList(int $order_id): array
    {
        if (empty($order_id)) {
            throw new \Exception('ID заказа для получения товаров заказа не задан');
        }

        // Формируем строку с кукой
        $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/delivery/order');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['order_id' => $order_id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json-patch+json',
            'Cookie: ' . $cookie_string  // Передаем куку в заголовке
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  // Альтернативный способ передачи куки
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        #curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $content = curl_exec($ch);

        return json_decode($content, true);
    }

    # Проверка ранее созданного аналогичного заказа
    public function checkOrder(int $userId, string $idempotency): bool
    {
        try {

            if (empty($userId)) {
                throw new \Exception('ID пользователя пустой');
            }
            if ($idempotency == '') {
                throw new \Exception('Не передан хеш идемпотентности');
            }

            $query = "SELECT order_id 
              FROM orders 
              WHERE idempotency = '".$idempotency."' 
              AND user_id = :user_id 
              LIMIT 1";
            $statement = $this->pdo->prepare($query);
            $statement->execute([':user_id' => $userId]);
            $order = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$order) {

                return true;

            } else {

                return false;
            }

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