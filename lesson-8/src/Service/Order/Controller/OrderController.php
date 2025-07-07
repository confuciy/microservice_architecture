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
     *                 required={"amount", "idempotency", "warehouse_list"},
     *                 @OA\Property(description="Сумма заказа", property="amount", type="number", format="number"),
     *                 @OA\Property(description="Хеш идемпотентности", property="idempotency", type="string", format="string"),
     *                 @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseOrderCreateItem"))
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
     *     @OA\Property(property="warehouse_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseOrderCreateItem"))
     * )
     *
     * @OA\Schema(
     *     schema="WarehouseOrderCreateItem",
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

            # Получение биллинг-аккаунта
            $billing = $this->order->getBilling();
            if (!sizeof($billing)) {
                throw new \Exception('Данные биллинг-аккаунта пусты');
            }

            # Сумма заказа больше имеющихся средств
            if ($billing['amount'] < $data['amount']) {

                # Добавляем оповещение
                $this->helper->setNotification($data['user_id'], 'create_order_error', '[✗] Сумма заказа ['.$data['amount'].'] больше имеющихся на биллинг-аккаунте средств ['.$billing['amount'].']');

                throw new \Exception('Сумма заказа ['.$data['amount'].'] больше имеющихся на биллинг-аккаунте средств ['.$billing['amount'].']');
            }

//            # Уменьшаем сумму биллинг-аккаунта
//            $billing_amount = $this->order->billingAmount('minus', $data['amount']);
//
//            if (!isset($billing_amount['billing_id'])) {
//                throw new \Exception('Не удалось снять необходимую сумму');
//            }

            # Создаем заказ
            $order = $this->order->create($data);

            # Создаем saga
            $order_saga = $this->order->createSaga($data);

            # Обновляем заказ - статус "Ожидает оплаты"
            $this->order->updateSaga($data['data']['order_id'], 'status', 1);

            /* {{{ */
                # Уменьшаем сумму биллинг аккаунта

                # Данные для отправки
                $data_billing = [
                    'type' => 'saga',
                    'data' => [
                        'action' => 'minus',
                        'user_id' => $data['user_id'],
                        'order_id' => $data['order_id'],
                        'order_saga_id' => $data['order_saga_id'],
                        'amount' => $data['amount'],
                        'warehouse_list' => $data['warehouse_list']
                    ]
                ];

                # Отправляем сообщение в RabbitMQ
                $this->helper->rabbitmqSend('service-billing', json_encode($data_billing));
            /* }}} */

            # Добавляем оповещение
            $this->helper->setNotification($data['user_id'], 'create_order_ok', '[✓] Заказ на сумму '.$data['amount'].' c ID '.$order['order_id'].' успешно создан');

            if (isset($_POST['reload'])) {

                header('Location: /user/order');

            } else {

                http_response_code(201);
                echo json_encode($order);
                return;
            }

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
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
     *     @OA\Property(property="warehouse_action_list", type="array", @OA\Items(ref="#/components/schemas/WarehouseOrderGetItem"))
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
            echo json_encode(['order_list' => $order_list]);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
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