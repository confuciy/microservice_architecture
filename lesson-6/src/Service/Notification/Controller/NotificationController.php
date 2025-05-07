<?php
namespace App\Service\Notification\Controller;

use App\Service\Notification\Model\Notification;

class NotificationController
{
    private $notification;

    public function __construct()
    {
        $this->notification = new Notification();
    }

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