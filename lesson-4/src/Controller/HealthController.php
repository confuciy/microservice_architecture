<?php
namespace App\Controller;

class HealthController
{
    public function getHealth(): void
    {
        try {

            http_response_code(200);

            echo json_encode([
                'status'    => 'ok',
            ]);

        } catch (\Exception $e) {

            http_response_code(404);

            echo json_encode(['error' => $e->getMessage()]);

            return;
        }
    }
}