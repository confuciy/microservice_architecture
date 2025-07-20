<?php
namespace App\Service\Health\Controller;

class HealthController
{
    public function get(): void
    {
        try {

            http_response_code(200);

            echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

        } catch (\Exception $e) {

            http_response_code(404);

            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

            return;
        }
    }
}