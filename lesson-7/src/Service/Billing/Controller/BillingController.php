<?php
namespace App\Service\Billing\Controller;

use App\Service\Billing\Model\Billing;
use App\Helper\Helper;

/**
 * @OA\Tags(
 *     name="Billing | Сервис биллинга"
 * )
 */
class BillingController
{
    private $billing;
    private $helper;

    public function __construct()
    {
        $this->billing = new Billing();
        $this->helper = new Helper();
    }

    /**
     * @OA\Post(
     *     path="/billing",
     *     summary="Создание биллинг-аккаунта",
     *     description="",
     *     tags={"Billing | Сервис биллинга"},
     *     security={{"cookieAuth": {}}},
     *     operationId="billing_create",
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
     *                      "billing_id": 0
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

            $billing = $this->billing->create($data);

            http_response_code(201);
            echo json_encode($billing);

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
     *     path="/billing",
     *     summary="Получение биллинг-аккаунта",
     *     description="",
     *     tags={"Billing | Сервис биллинга"},
     *     security={{"cookieAuth": {}}},
     *     operationId="billing_get",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Success",
     *                  value={
     *                      "billing_id": 0
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
    public function get(int $userId = 0): void
    {
        # Если не передан ID пользователя
        if (empty($userId)) {
            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null) {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }
        }

        try {

            # Если не передан ID пользователя
            if (empty($userId)) {

                $jwt_token_data = $this->helper->getJWTtokenData();

                if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                    http_response_code(401);
                    echo json_encode(['error' => 'You a not login']);
                    return;
                }

                $userId = $jwt_token_data['user_id'];
            }

            $order_list = $this->billing->get($userId);

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