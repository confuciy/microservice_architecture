<?php

namespace App\Service\Metric\Controller;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;
use Prometheus\Summary;
use Prometheus\Storage\Redis;

class PrometheusMetric
{
    private CollectorRegistry $registry;
    private Summary $httpRequestLatency;
    private Counter $httpRequestTotal;
    private Counter $httpErrorTotal;
    private static ?PrometheusMetric $instance = null;

    private const HTTP_REQUEST_DURATION_SECONDS = 'http_request_duration_seconds';
    private const HTTP_REQUEST_TOTAL = 'http_request_total';
    private const HTTP_ERROR_TOTAL = 'http_error_total';
    private const OTUS_HTTP_RDS = 'otus_http_rds'; // Новая константа
    private const DEFAULT_LABELS =  ['method', 'code'];


    public function __construct()
    {
        try{

            $redis = new \Redis();

            // Настройки для подключения к Redis
            $redisOptions = [
                'host' => getenv('redis_host'),
                'port' => getenv('redis_port'),
                'password' => getenv('redis_password'),
                'timeout' => getenv('redis_timeout'), // Таймаут подключения (опционально)
                'read_timeout' => getenv('redis_read_timeout'), // Таймаут чтения (опционально)
                'persistent_connections' => getenv('redis_persistent_connections')// Постоянные соединения (опционально)
            ];

            // Создаем адаптер Redis с настройками
            $adapter = new Redis($redisOptions);

        } catch (\RedisException $e) {
            throw new \Exception('Redis connection failed: '. $e->getMessage());
        }

        $this->registry = new CollectorRegistry($adapter);

        $this->httpRequestLatency = $this->registry->getOrRegisterSummary(
            'api_request',
            self::HTTP_REQUEST_DURATION_SECONDS,
            'Duration of HTTP requests',
            self::DEFAULT_LABELS,
            84600,
            [0.01, 0.05, 0.5, 0.95, 0.99]
        );

        $this->otusHttpRdsLatency = $this->registry->getOrRegisterSummary(
            'api_request',
            self::OTUS_HTTP_RDS,
            'Duration of page opening for OTUS HTTP RDS',
            self::DEFAULT_LABELS,
            84600,
            [0.01, 0.05, 0.5, 0.95, 0.99]
        );

        $this->httpRequestTotal = $this->registry->getOrRegisterCounter(
            'api_request',
            self::HTTP_REQUEST_TOTAL,
            'Total number of HTTP requests',
            self::DEFAULT_LABELS
        );

        $this->httpErrorTotal = $this->registry->getOrRegisterCounter(
            'api_request',
            self::HTTP_ERROR_TOTAL,
            'Total number of HTTP errors',
            ['method']
        );
    }
    public static function getInstance(): PrometheusMetric
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function startTimer(string $method): float
    {
        return microtime(true);
    }

    public function observeRequest(float $startTime, string $method, int $code): void
    {
        $duration = microtime(true) - $startTime;

        try{

            $this->httpRequestLatency->observe($duration, ['method' => $method, 'code' => $code]);
            $this->otusHttpRdsLatency->observe($duration, ['method' => $method, 'code' => $code]);

        } catch (\Throwable $e){
            // do nothing;
        }

        try{

            $this->httpRequestTotal->inc(['method' => $method, 'code' => $code]);

        } catch (\Throwable $e){
            // do nothing;
        }
    }

    public function incError(string $method): void
    {
        try{
            $this->httpErrorTotal->inc(['method' => $method]);
        } catch (\Throwable $e){
            // do nothing;
        }
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    public function renderMetric(): string
    {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}