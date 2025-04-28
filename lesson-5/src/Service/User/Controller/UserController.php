<?php
namespace App\Service\User\Controller;

use App\Service\User\Model\User;

class UserController
{
    private $user;

    public function __construct()
    {
        $this->user = new User();
    }

    public function createUser(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null) {

            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        try {

            $user = $this->user->create($data);

            http_response_code(201);
            echo json_encode($user);

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

    public function getUser(int $userId): void
    {
        try {

            $user = $this->user->get($userId);
            http_response_code(200);
            echo json_encode($user);

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    public function deleteUser(int $userId): void
    {
        try {

            $user = $this->user->delete($userId);
            http_response_code(200);
            echo json_encode($user);

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    public function updateUser(int $userId): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null) {

            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        try {

            $user = $this->user->update($userId, $data);
            http_response_code(200);
            echo json_encode($user);

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }
}