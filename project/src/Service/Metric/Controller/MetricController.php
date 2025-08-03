<?php
namespace App\Service\Metric\Controller;

class MetricController
{
    public function get(): void
    {
        try {

            http_response_code(200);

            $metrics = PrometheusMetric::getInstance();
            header('Content-Type: text/plain');

            echo $metrics->renderMetric();

        } catch (\Exception $e) {

            http_response_code(404);

            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

            return;
        }
    }
}