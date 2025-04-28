<?php
//# Устанавливаем заголовок контента на application/json
//header('Content-Type: application/json');
//
//# Получаем текущий путь
//$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//$requestPath = rtrim($requestPath, '/');
//
//# Проверяем, соответствует ли путь /health/
//if ($requestPath === '/health') {
//
//    header('Content-Type: application/json');
//    echo json_encode(['status' => 'OK']);
//
//} else {
//
//    header('Content-Type: application/json');
//    echo json_encode(['status' => 'AR.OV: '.time()]);
//}

require __DIR__ . '/../vendor/autoload.php';
#use App\Metrics\PrometheusMetrics;

$dispatcher = require __DIR__ . '/routes.php';

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {

    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

# Metrics
$metrics = App\Service\Metrics\Controller\PrometheusMetrics::getInstance();
$startTime = $metrics->startTimer($httpMethod);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

//echo '<pre>'; print_r($uri); echo '</pre>';
//echo '<pre>'; print_r($routeInfo); echo '</pre>';

switch ($routeInfo[0]) {

    case FastRoute\Dispatcher::NOT_FOUND:

        http_response_code(404);
        $metrics->observeRequest($startTime, $httpMethod, 404);
        echo json_encode(['error' => 'Not found']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:

        http_response_code(405);
        $metrics->observeRequest($startTime, $httpMethod, 405);
        echo json_encode(['error' => 'Method not allowed']);
        break;

    case FastRoute\Dispatcher::FOUND:

        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        [$class, $method] = $handler;

        $controller = new $class();
        try {

            $response = call_user_func_array([$controller, $method], $vars);

        } catch (Throwable $e) {

            http_response_code(500);
            $metrics->incError($httpMethod);
            $metrics->observeRequest($startTime, $httpMethod, 500);
            echo json_encode(['error' => 'Internal Server Error']);
            return;
        }

        $code = http_response_code();

        if ($code >= 500){

            $metrics->incError($httpMethod);
        }

        $metrics->observeRequest($startTime, $httpMethod, $code);
        break;
}