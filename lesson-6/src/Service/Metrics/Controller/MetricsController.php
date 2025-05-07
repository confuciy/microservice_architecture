<?php
namespace App\Service\Metrics\Controller;
//use App\Metrics\PrometheusMetrics;

class MetricsController
{
    public function get(): void
    {
        try {

            http_response_code(200);

            $metrics = PrometheusMetrics::getInstance();
            header('Content-Type: text/plain');

            echo $metrics->renderMetrics();

        } catch (\Exception $e) {

            http_response_code(404);

            echo json_encode(['error' => $e->getMessage()]);

            return;
        }
    }
}