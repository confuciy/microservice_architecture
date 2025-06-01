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
     *                 @OA\Property(
     *                     description="ID пользователя",
     *                     property="user_id",
     *                     type="integer",
     *                     format="integer"
     *                 ),
     *                 @OA\Property(
     *                     description="Сумма заказа",
     *                     property="amount",
     *                     type="number",
     *                     format="number"
     *                 ),
     *                 @OA\Property(
     *                     description="Хеш идемпотентности",
     *                     property="idempotency",
     *                     type="string",
     *                     format="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Success",
     *                  value={
     *                      "order_id": 0
     *                  },
     *                  summary=""
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Error",
     *                  value={
     *                      "error": "Invalid JSON data"
     *                  },
     *                  summary=""
     *              ),
     *          )
     *     )
     * )
     *
     * @return void
     * @throws Exception
     */
    public function create(array $data = []): void
    {
        # Если не переданы данные
        if (!sizeof($data)) {

            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null) {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }
        }

        try {

            $order = $this->order->create($data);

            http_response_code(201);
            echo json_encode($order);

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
     *     summary="Получение списка оповещений",
     *     description="",
     *     tags={"Order | Сервис заказов"},
     *     security={{"cookieAuth": {}}},
     *     operationId="order_get",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Success",
     *                  value={
     *                      "order_id": 0
     *                  },
     *                  summary=""
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Error",
     *                  value={
     *                      "error": "Invalid JSON data"
     *                  },
     *                  summary=""
     *              ),
     *          )
     *     )
     * )
     *
     * @return void
     * @throws Exception
     */
    public function get(): void
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login']);
                return;
            }

            $order_list = $this->order->get($jwt_token_data['user_id']);

            http_response_code(200);
            echo json_encode($order_list);

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