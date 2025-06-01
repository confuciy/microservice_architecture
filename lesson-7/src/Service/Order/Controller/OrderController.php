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
     *                 required={"amount", "idempotency"},
     *                 @OA\Property(description="Сумма заказа", property="amount", type="number", format="number"),
     *                 @OA\Property(description="Хеш идемпотентности", property="idempotency", type="string", format="string")
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
     *     @OA\Property(description="Хеш идемпотентности", property="idempotency", type="string")
     * )
     *
     * @throws \Exception
     */
    public function create(array $data = [])
    {
        try {

            # Если не переданы данные из BillingReceive
            if (!sizeof($data)) {

                if (!sizeof($_POST)) {
                    throw new \Exception('JSON поврежден');
                }

                # Данные пользователя
                $data = $_POST;
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

            # Сумма заказа больше имеющихсы на средств
            if ($billing['amount'] < $data['amount']) {

                # Добавляем оповещение
                $this->helper->setNotification($data['user_id'], 'create_order_error', 'Сумма заказа ['.$data['amount'].'] больше имеющихся на биллинг-аккаунта средств ['.$billing['amount'].']');

                throw new \Exception('Сумма заказа ['.$data['amount'].'] больше имеющихся на биллинг-аккаунта средств ['.$billing['amount'].']');
            }

            # Создаем заказ
            $order = $this->order->create($data);

            # Уменьшаем сумму биллинг аккаунта

            # Данные для отправки
            $data_billing = [
                'action' => 'minus',
                'data' => [
                    'user_id' => $data['user_id'],
                    'amount' => $data['amount']
                ]
            ];

            # Создаем аккаунт в сервисе биллинга
            # Отправляем сообщение в RabbitMQ
            $this->helper->rabbitmqSend('service-billing', json_encode($data_billing));

            # Добавляем оповещение
            $this->helper->setNotification($data['user_id'], 'create_order_ok', 'Заказ на сумму '.$data['amount'].' успешно создан');

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
     *     @OA\Property(property="order_list", type="array", @OA\Items(ref="#/components/schemas/OrderItem")),
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