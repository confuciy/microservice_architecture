<?php
namespace App\Service\Notification\Controller;

use App\Service\Notification\Model\Notification;
use App\Helper\Helper;

/**
 * @OA\Tags(
 *     name="Notification | Сервис оповещений"
 * )
 */
class NotificationController
{
    private $notification;
    private $helper;

    public function __construct()
    {
        $this->notification = new Notification();
        $this->helper = new Helper();
    }

    /**
     * @OA\Post(
     *     path="/notification",
     *     summary="Добавление оповещения",
     *     description="",
     *     tags={"Notification | Сервис оповещений"},
     *     security={{"cookieAuth": {}}},
     *     operationId="notification_create",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"user_id", "action", "message"},
     *                 @OA\Property(description="ID пользователя", property="user_id", type="integer", format="integer"),
     *                 @OA\Property(description="Действие", property="action", type="string", format="string"),
     *                 @OA\Property(description="Сообщение", property="message", type="string", format="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/NotificationCreateResponse")
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
     *     schema="NotificationCreateResponse",
     *     title="Оповещения пользователя",
     *     description="",
     *     @OA\Property(property="notification_id", type="integer", example="1"),
     *     @OA\Property(description="ID пользователя", property="user_id", type="integer"),
     *     @OA\Property(description="Действие", property="action", type="string"),
     *     @OA\Property(description="Сообщение", property="message", type="string")
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


            $notification = $this->notification->create($data);

            http_response_code(201);
            echo json_encode($notification);
            return;

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
     *     path="/notification",
     *     summary="Список оповещений",
     *     description="",
     *     tags={"Notification | Сервис оповещений"},
     *     security={{"cookieAuth": {}}},
     *     operationId="notification_get",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/NotificationResponse")
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
     *     schema="NotificationResponse",
     *     title="Список оповещений пользователя",
     *     description="",
     *     @OA\Property(property="notification_list", type="array", @OA\Items(ref="#/components/schemas/NotificationItem"))
     * )
     *
     * @OA\Schema(
     *     schema="NotificationItem",
     *     title="Оповещение пользователя",
     *     description="",
     *     @OA\Property(property="notification_id", type="integer", example="1"),
     *     @OA\Property(property="user_id", type="integer", example="1"),
     *     @OA\Property(property="action", type="string", example="create_user_ok"),
     *     @OA\Property(property="message", type="string", example="Пользователь с почтой email1@email.com создан с id 1"),
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

            $notification_list = $this->notification->get($jwt_token_data['user_id']);

            http_response_code(200);
            echo json_encode(['notification_list' => $notification_list]);
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