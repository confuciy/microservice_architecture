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
     *                 required={"user_id", "amount"},
     *                 @OA\Property(description="ID пользователя", property="user_id", type="integer", format="integer"),
     *                 @OA\Property(description="Сумма", property="amount", type="number", format="number")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/BillingCreateResponse")
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
     *     schema="BillingCreateResponse",
     *     title="Биллинг-аккаунта пользователя",
     *     description="",
     *     @OA\Property(property="billing_id", type="integer", example="1")
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


            if ($this->billing->checkBillingExists($data['user_id']) == true) {
                throw new \Exception('Биллниг-аккаунт пользователя '.$data['user_id'].' уже существует');
            }

            $billing = $this->billing->create($data);

            http_response_code(201);
            return json_encode($billing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
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
     *          @OA\JsonContent(ref="#/components/schemas/BillingGetResponse")
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
     *     schema="BillingGetResponse",
     *     title="Биллинг-аккаунт пользователя",
     *     description="",
     *     @OA\Property(property="billing", ref="#/components/schemas/BillingItem")
     * )
     *
     * @OA\Schema(
     *     schema="BillingItem",
     *     title="Биллинг-аккаунт пользователя",
     *     description="",
     *     @OA\Property(property="billing_id", type="integer", example="1"),
     *     @OA\Property(property="user_id", type="integer", example="1"),
     *     @OA\Property(property="amount", type="number", example="100.25"),
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

            $order_list = $this->billing->get($userId);

            http_response_code(200);
            echo json_encode($order_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    /**
     * @OA\Post(
     *     path="/billing/amount",
     *     summary="Изменение суммы биллинг-аккаунта",
     *     description="",
     *     tags={"Billing | Сервис биллинга"},
     *     security={{"cookieAuth": {}}},
     *     operationId="billing_amount",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"amount"},
     *                 @OA\Property(description="Сумма", property="amount", type="number", format="number")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/BillingItem")
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
     * @throws \Exception
     */
    public function amount(array $data = [])
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

            if (!isset($data['amount'])) {
                throw new \Exception('Не передана сумма');
            }

            if ($data['amount'] <= 0) {
                throw new \Exception('Сумма пополнения должна быть больше нуля');
            }

            # Изменение суммы биллниг-аккаунта
            $billing = $this->billing->amount($data);

            if ($data['action'] == 'plus') {

                $this->helper->setNotification($data['user_id'], 'billing-amount', '[✓] Сумма биллингового аккаунта успешно пополнена на '.$data['amount']);

            } else {

                $this->helper->setNotification($data['user_id'], 'billing-amount', '[✓] Сумма биллингового аккаунта успешно уменьшена на '.$data['amount']);
            }

            if (isset($_POST['reload'])) {

                header('Location: /user/billing');

            } else {

                http_response_code(200);
                echo json_encode($billing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                return;
            }

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
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