<?php
namespace App\Service\Delivery\Controller;

use App\Service\Delivery\Model\Delivery;
use App\Helper\Helper;

/**
 * @OA\Tags(
 *     name="Delivery | Сервис доставки"
 * )
 */
class DeliveryController
{
    private $delivery;
    private $helper;

    public function __construct()
    {
        $this->delivery = new Delivery();
        $this->helper = new Helper();
    }

    /**
     * @OA\Post(
     *     path="/delivery",
     *     summary="Резервирование курьера",
     *     description="",
     *     tags={"Delivery | Сервис доставки"},
     *     security={{"cookieAuth": {}}},
     *     operationId="delivery_create",
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
     *          @OA\JsonContent(ref="#/components/schemas/DeliveryCreateResponse")
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
     *     schema="DeliveryCreateResponse",
     *     title="Зарезервированный курьер",
     *     description="",
     *     @OA\Property(property="delivery_action_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1")
     *     @OA\Property(property="delivery _date", type="string", example="2025-05-29"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807")
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

            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('Не удалось определить ID заказа');
            }

            $delivery = $this->delivery->getDeliveryFree();

            if (isset($delivery['delivery_id']) and !empty($delivery['delivery_id'])) {

                $data['delivery_id'] = $delivery['delivery_id'];

                $delivery = $this->delivery->create($data);

                http_response_code(201);
                return json_encode($delivery);

            } else {

                throw new \Exception('Не удалось получить свободного курьера');
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
     *     path="/delivery",
     *     summary="Список курьеров",
     *     description="",
     *     tags={"Delivery | Сервис доставки"},
     *     security={{"cookieAuth": {}}},
     *     operationId="delivery_get",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/DeliveryGetResponse")
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
     *     schema="DeliveryGetResponse",
     *     title="Список курьеров",
     *     description="",
     *     @OA\Property(property="delivery_list", type="array", @OA\Items(ref="#/components/schemas/DeliveryGetItem"))
     * )
     *
     * @OA\Schema(
     *     schema="DeliveryGetItem",
     *     title="Курьер",
     *     description="",
     *     @OA\Property(property="delivery_id", type="integer", example="1"),
     *     @OA\Property(property="courier", type="string", example="Курьер #1"),
     *     @OA\Property(property="free", type="number", example="1"),
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
                http_response_code(401);
                throw new \Exception('Пользователь не авторизован');
            }

            $delivery_list = $this->delivery->getDeliveryList();

            http_response_code(200);
            echo json_encode($delivery_list);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @OA\Delete(
     *     path="/delivery",
     *     summary="Освобождение курьера",
     *     description="",
     *     tags={"Delivery | Сервис доставки"},
     *     security={{"cookieAuth": {}}},
     *     operationId="delivery_delete",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"order_id", "delivery_id"},
     *                 @OA\Property(description="ID заказа", property="order_id", type="integer", format="integer"),
     *                 @OA\Property(description="ID курьера", property="delivery_id", type="integer", format="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/DeliveryDeleteResponse")
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
     *     schema="DeliveryDeleteResponse",
     *     title="Освобожденный курьер",
     *     description="",
     *     @OA\Property(property="delivery_action_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1")
     *     @OA\Property(property="delivery _date", type="string", example="2025-05-29"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807")
     * )
     *
     * @throws \Exception
     */
    public function  delete(array $data = [])
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

            if (!isset($data['order_id']) or empty($data['order_id'])) {
                throw new \Exception('Не удалось определить ID заказа');
            }
            if (!isset($data['delivery_id']) or empty($data['delivery_id'])) {
                throw new \Exception('Не удалось определить ID курьера');
            }

            $delivery = $this->delivery->delete($data);

            http_response_code(200);
            return json_encode($delivery);

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
     *     path="/delivery/order",
     *     summary="Получение зарезервированного курьера заказа",
     *     description="",
     *     tags={"Delivery | Сервис доставки"},
     *     security={{"cookieAuth": {}}},
     *     operationId="delivery_order",
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
     *          @OA\JsonContent(ref="#/components/schemas/DeliveryOrderGetResponse")
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
     *     schema="DeliveryOrderGetResponse",
     *     title="Зарезервированный курьер",
     *     description="",
     *     @OA\Property(property="delivery_action_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1")
     *     @OA\Property(property="delivery _date", type="string", example="2025-05-29"),
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

            $delivery = $this->delivery->getDeliveryOrder($data['order_id']);

            http_response_code(200);
            echo json_encode($delivery);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @OA\Post(
     *     path="/delivery/delivered",
     *     summary="Подтверждение доставки заказа курьером",
     *     description="",
     *     tags={"Delivery | Сервис доставки"},
     *     security={{"cookieAuth": {}}},
     *     operationId="delivery_delivered",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"order_id", "delivery_id"},
     *                 @OA\Property(description="ID заказа", property="order_id", type="integer", format="integer"),
     *                 @OA\Property(description="ID курьера", property="delivery_id", type="integer", format="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/DeliveryDeliveredResponse")
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
     *     schema="DeliveryDeliveredResponse",
     *     title="Освобожденный курьер, выполненный заказ",
     *     description="",
     *     @OA\Property(property="delivery_action_id", type="integer", example="1"),
     *     @OA\Property(property="delivery_id", type="integer", example="1"),
     *     @OA\Property(property="order_id", type="integer", example="1")
     *     @OA\Property(property="delivery _date", type="string", example="2025-05-29"),
     *     @OA\Property(property="status", type="integer", example="1"),
     *     @OA\Property(property="date_insert", type="string", example="2025-05-29 03:03:17.807"),
     *     @OA\Property(property="courier", type="string", example="Курьер #1"),
     *     @OA\Property(property="free", type="number", example="1")
     * )
     *
     * @throws \Exception
     */
    public function delivered(array $data = [])
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

            # Освобождение курьера
            $this->delivery->delete($data);

            # Получение бронирования заказа
            $delivery = $this->delivery->getDeliveryOrder($data['order_id']);

            /* {{{ */

                # Заказ доставлен

                # Данные для отправки
                $data_order = [
                    'action' => 'delivered',
                    'data' => [
                        'user_id' => $data['user_id'],
                        'order_id' => $delivery['order_id']
                    ]
                ];

                # Отправляем сообщение в RabbitMQ
                $this->helper->rabbitmqSend('service-order', json_encode($data_order));
            /* }}} */

            http_response_code(200);
            echo json_encode($delivery);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }
}