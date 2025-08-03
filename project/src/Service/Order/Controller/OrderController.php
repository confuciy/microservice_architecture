<?php
namespace App\Service\Order\Controller;

use App\Service\Order\Model\Order;
use App\Helper\Helper;

/**
 * @OA\Tags(
 *     name="Order | Сервис заказов"
 * )
 */
class OrderController
{
    private $order;
    private $helper;

    public function __construct()
    {
        $this->order = new Order();
        $this->helper = new Helper();
    }

    /**
     * @OA\Post(
     *     path="/order",
     *     summary="Добавление заказа",
     *     description="",
     *     tags={"Order | Сервис заказов"},
     *     security={{"cookieAuth": {}}},
     *     operationId="order_create",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"warehouse_list"},
     *                 @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/OrderCreateItem"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/OrderCreateResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="OrderCreateResponse",
     *     title="Заказ пользователя",
     *     description="",
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(description="ID пользователя", property="user_id", type="integer"),
     *     @OA\Property(description="Сумма заказа", property="amount", type="number"),
     *     @OA\Property(description="Хеш идемпотентности", property="idempotency", type="string"),
     *     @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/OrderCreateItem"))
     * )
     *
     * @OA\Schema(
     *     schema="OrderCreateItem",
     *     title="Список товаров",
     *     description="",
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="count", type="number", example="1")
     * )
     *
     * @throws \Exception
     */
    public function create(array $data = [])
    {
        try {

            $transaction = 0;

            # Получаем данные
            if (!sizeof($data)) {

                $data = json_decode(file_get_contents('php://input'), true);

                if ($data === null and sizeof($_POST) > 0) {

                    # Данные пользователя
                    $data = $_POST;
                }
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }

            if (!isset($data['user_id'])) {

                $jwt_token_data = $this->helper->getJWTtokenData();

                if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {
                    throw new \Exception('Пользователь не авторизован');
                }

                $userId = $jwt_token_data['user_id'];

                if (empty($userId)) {
                    throw new \Exception('Не удалось определить пользователя');
                }

                $data['user_id'] = $userId;
            }

            if (!isset($data['warehouse_list']) or $data['warehouse_list'] == '') {
                throw new \Exception('Список товаров пустой');
            }

            # Для запросов через сайт/Postman
            if (is_string($data['warehouse_list'])) {
                $data['warehouse_list'] = json_decode($data['warehouse_list'], true);
            }

            $idempotency = md5($data['user_id'].'_'.json_encode($data['warehouse_list'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));

            $data['idempotency'] = $idempotency;

            # Получение заказов пользователя
            $order_check = $this->order->checkOrder($data['user_id'], $idempotency);

            # Если reorder = 1, позволяем создать заказ повторно
            if ($order_check == false and empty($data['reorder'])) {
                throw new \Exception('Подобный заказ уже существует!');
            }

            # Получение биллинг-аккаунта
            $billing = $this->order->getBilling();
            if (!sizeof($billing)) {
                throw new \Exception('Данные биллинг-аккаунта пусты');
            }

            # Расчитываем стоимость заказа
            $data['amount'] = 0;
            foreach ($data['warehouse_list'] as $warehouse_item) {

                $data['amount'] += ($warehouse_item['price'] * $warehouse_item['count']);
            }

            # Сумма заказа больше имеющихся средств
            if ($billing['amount'] < $data['amount']) {

                # Добавляем оповещение
                $this->helper->setNotification($data['user_id'], 'create_order_error', '[✗] Сумма заказа ['.$data['amount'].'₽] больше имеющихся на биллинг-аккаунте средств ['.$billing['amount'].'₽]');

                throw new \Exception('Сумма заказа ['.$data['amount'].'₽] больше имеющихся на биллинг-аккаунте средств ['.$billing['amount'].'₽]');
            }

//            # Уменьшаем сумму биллинг-аккаунта
//            $billing_amount = $this->order->billingAmount('minus', $data['amount']);
//
//            if (!isset($billing_amount['billing_id'])) {
//                throw new \Exception('Не удалось снять необходимую сумму');
//            }

            $this->order->pdo->beginTransaction();

                $transaction = 1;

                # Создаем заказ
                $order = $this->order->create($data);

                # Создаем saga
                $order_saga = $this->order->createSaga($order);

                # Обновляем заказ - статус "Ожидает оплаты"
                $this->order->updateOrder($order['order_id'], 'status', 1);

                # Обновляем saga заказа - статус "Ожидает оплаты"
                $this->order->updateSaga($order_saga['order_saga_id'], 'status', 1);

            $this->order->pdo->commit();

//            /* {{{ */
//                # Уменьшаем сумму биллинг аккаунта
//
//                # Данные для отправки
//                $data_billing = [
//                    'type' => 'saga',
//                    'data' => [
//                        'action' => 'minus',
//                        'user_id' => $order['user_id'],
//                        'order_id' => $order['order_id'],
//                        'order_saga_id' => $order_saga['order_saga_id'],
//                        'amount' => $order['amount'],
//                        'warehouse_list' => $order['warehouse_list']
//                    ]
//                ];
//
//                # Отправляем сообщение в RabbitMQ
//                $this->helper->rabbitmqSend('service-billing', json_encode($data_billing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
//            /* }}} */
//
//            # Добавляем оповещение
//            $this->helper->setNotification($order['user_id'], 'create_order_ok', '[✓] Заказ на сумму '.$order['amount'].' c ID = '.$order['order_id'].' успешно создан');

//            if (isset($_POST['reload'])) {
//
//                header('Location: /user/order');
//
//            } else {

                http_response_code(201);
                echo json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                return;
//            }

        } catch (\Throwable $e) {

            if ($transaction == 1) {
                $this->order->pdo->rollBack();
            }

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            if ($transaction == 1) {
                $this->order->pdo->rollBack();
            }

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * @OA\Get(
     *     path="/order",
     *     summary="Список заказов",
     *     description="",
     *     tags={"Order | Сервис заказов"},
     *     security={{"cookieAuth": {}}},
     *     operationId="order_get",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/OrderGetResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="OrderGetResponse",
     *     title="Список заказов пользователя",
     *     description="",
     *     @OA\Property(property="order_list", type="array", @OA\Items(ref="#/components/schemas/OrderItem"))
     * )
     *
     * @OA\Schema(
     *     schema="OrderItem",
     *     title="Заказ пользователя",
     *     description="",
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="user_id", type="integer", example="1"),
     *     @OA\Property(property="amount", type="number", example="100.25"),
     *     @OA\Property(property="idempotency", type="string", example="qweafasrqwe2sadasd..."),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807"),
     *     @OA\Property(property="warehouse_action_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseOrderGetItem")),
     *     @OA\Property(property="delivery_action_list", type="array", @OA\Items(ref="#/components/schemas/DeliveryOrderGetItem"))
     * )
     *
     * @OA\Schema(
     *     schema="WarehouseOrderGetItem",
     *     title="Список товаров",
     *     description="",
     *     @OA\Property(property="warehouse_action_id", type="integer", example="1"),
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="action", type="string", example="plus"),
     *     @OA\Property(property="count", type="number", example="1"),
     *     @OA\Property(property="amount", type="number", example="100.25"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807")
     * )
     *
     * @OA\Schema(
     *     schema="DeliveryOrderGetItem",
     *     title="Зарезервированный курьер",
     *     description="",
     *     @OA\Property(property="delivery_action_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_date", type="string", example="2025-05-29"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807"),
     *     @OA\Property(property="courier", type="string", example="Курьер #1"),
     *     @OA\Property(property="free", type="number", example="1")
     * )
     *
     * @throws \Exception
     */
    public function get()
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {
                throw new \Exception('Пользователь не авторизован');
            }

            $userId = $jwt_token_data['user_id'];

            if (empty($userId)) {
                throw new \Exception('Не удалось определить пользователя');
            }

            # Получение заказов пользователя
            $order_list = $this->order->getOrderList($userId);

            http_response_code(200);
            echo json_encode(['order_list' => $order_list], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * @OA\Post(
     *     path="/order/check",
     *     summary="Проверка заказа перед созданием",
     *     description="",
     *     tags={"Order | Сервис заказов"},
     *     security={{"cookieAuth": {}}},
     *     operationId="order_check",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"warehouse_list"},
     *                 @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/OrderCheckItem"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/OrderCheckResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="OrderCheckResponse",
     *     title="Заказ пользователя",
     *     description="",
     *     @OA\Property(property="order_check", type="bool", example="true")
     * )
     *
     * @OA\Schema(
     *     schema="OrderCheckItem",
     *     title="Список товаров",
     *     description="",
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="name", type="string", example="Часы"),
     *     @OA\Property(property="price", type="number", example="2600"),
     *     @OA\Property(property="count", type="number", example="1")
     * )
     *
     * @throws \Exception
     */
    public function check(array $data = [])
    {
        try {

            # Получаем данные
            if (!sizeof($data)) {

                $data = json_decode(file_get_contents('php://input'), true);

                if ($data === null and sizeof($_POST) > 0) {

                    # Данные пользователя
                    $data = $_POST;
                }
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }


            if (!isset($data['user_id'])) {

                $jwt_token_data = $this->helper->getJWTtokenData();

                if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {
                    throw new \Exception('Пользователь не авторизован');
                }

                $userId = $jwt_token_data['user_id'];

                if (empty($userId)) {
                    throw new \Exception('Не удалось определить пользователя');
                }

                $data['user_id'] = $userId;
            }

            if (!isset($data['warehouse_list']) or $data['warehouse_list'] == '') {
                throw new \Exception('Список товаров пустой');
            }

            # Для запросов через сайт/Postman
            if (is_string($data['warehouse_list'])) {
                $data['warehouse_list'] = json_decode($data['warehouse_list'], true);
            }

            $idempotency = md5($data['user_id'].'_'.json_encode($data['warehouse_list'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));

            # Получение заказов пользователя
            $order_check = $this->order->checkOrder($data['user_id'], $idempotency);

            http_response_code(200);
            echo json_encode(['order_check' => $order_check, 'idempotency' => $idempotency], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * @OA\Post(
     *     path="/order/order",
     *     summary="Заказ",
     *     description="",
     *     tags={"Order | Сервис заказов"},
     *     security={{"cookieAuth": {}}},
     *     operationId="order_order",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"order_id"},
     *                 @OA\Property(description="ID заказа", property="order_id", type="integer", format="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/OrderOrderResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="OrderOrderResponse",
     *     title="Заказ пользователя",
     *     description="",
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="user_id", type="integer", example="1"),
     *     @OA\Property(property="amount", type="number", example="100.25"),
     *     @OA\Property(property="idempotency", type="string", example="qweafasrqwe2sadasd..."),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807"),
     *     @OA\Property(property="warehouse_action_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseOrderOrderGetItem")),
     *     @OA\Property(property="delivery_action_list", type="array", @OA\Items(ref="#/components/schemas/DeliveryOrderOrderGetItem"))
     * )
     *
     * @OA\Schema(
     *     schema="WarehouseOrderOrderGetItem",
     *     title="Список товаров",
     *     description="",
     *     @OA\Property(property="warehouse_action_id", type="integer", example="1"),
     *     @OA\Property(property="warehouse_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="action", type="string", example="plus"),
     *     @OA\Property(property="count", type="number", example="1"),
     *     @OA\Property(property="amount", type="number", example="100.25"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807")
     * )
     *
     *@OA\Schema(
     *     schema="DeliveryOrderOrderGetItem",
     *     title="Зарезервированный курьер",
     *     description="",
     *     @OA\Property(property="delivery_action_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_date", type="string", example="2025-05-29"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807"),
     *     @OA\Property(property="courier", type="string", example="Курьер #1"),
     *     @OA\Property(property="free", type="number", example="1")
     * )
     *
     * @throws \Exception
     */
    public function order(array $data = [])
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {
                throw new \Exception('Пользователь не авторизован');
            }

            $userId = $jwt_token_data['user_id'];

            if (empty($userId)) {
                throw new \Exception('Не удалось определить пользователя');
            }

            # Получаем данные
            if (!sizeof($data)) {

                $data = json_decode(file_get_contents('php://input'), true);

                if ($data === null and sizeof($_POST) > 0) {

                    # Данные пользователя
                    $data = $_POST;
                }
            }
            if ($data === null) {
                throw new \Exception('JSON поврежден');
            }

            # Получение заказа пользователя
            $order = $this->order->get($data['order_id'], $userId);

            http_response_code(200);
            echo json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

//    public function delete(int $userId): void
//    {
//        try {
//
//            $user = $this->user->delete($userId);
//            http_response_code(200);
//            echo json_encode($user);
//
//        } catch (\Exception $e) {
//
//            http_response_code(400);
//            echo json_encode(['error' => $e->getMessage()]);
//            return;
//        }
//    }
//
//    public function update(int $userId): void
//    {
//        $data = json_decode(file_get_contents('php://input'), true);
//
//        if ($data === null) {
//
//            http_response_code(400);
//            echo json_encode(['error' => 'Invalid JSON data']);
//            return;
//        }
//
//        try {
//
//            $user = $this->user->update($userId, $data);
//            http_response_code(200);
//            echo json_encode($user);
//
//        } catch (\Exception $e) {
//
//            http_response_code(400);
//            echo json_encode(['error' => $e->getMessage()]);
//            return;
//        }
//    }
}