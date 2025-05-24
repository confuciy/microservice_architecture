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
     *                 @OA\Property(
     *                     description="ID пользователя",
     *                     property="user_id",
     *                     type="integer",
     *                     format="integer"
     *                 ),
     *                 @OA\Property(
     *                     description="Действие",
     *                     property="action",
     *                     type="string",
     *                     format="string"
     *                 ),
     *                 @OA\Property(
     *                     description="Сообщение",
     *                     property="message",
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
     *                      "notification_id": 0
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
    public function create(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null) {

            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        try {

            $notification = $this->notification->create($data);

            http_response_code(201);
            echo json_encode($notification);

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
     *     summary="Получение списка оповещений оповещения",
     *     description="",
     *     tags={"Notification | Сервис оповещений"},
     *     security={{"cookieAuth": {}}},
     *     operationId="notification_get",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/ExampleSchema")
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
    /**
     * @OA\Schema(
     *     schema="ExampleSchema",
     *     title="Пример структуры данных",
     *     description="Объект с названием и списком элементов",
     *     @OA\Property(
     *         property="name",
     *         type="string",
     *         example="название"
     *     ),
     *     @OA\Property(
     *         property="list",
     *         type="array",
     *         @OA\Items(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="xx"),
     *             @OA\Property(property="count", type="integer", example=50)
     *         ),
     *         example={
     *             {"name": "xx", "count": 50},
     *             {"name": "xx", "count": 500}
     *         }
     *     )
     * )
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

            $notification_list = $this->notification->get($jwt_token_data['user_id']);

            http_response_code(200);
            echo json_encode($notification_list);

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