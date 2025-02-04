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
//    # Для проверки обращения к разным подам
//    #$hostname = gethostname();
//
//    # Возвращаем успешный JSON-ответ
//    echo json_encode([
//        'status'    => 'oke',
//        #"replica"   => $hostname
//    ]);
//
//} elseif (preg_match('/^\/otusapp\/(.*)\/health$/', $requestPath)) {
//
//    # Возвращаем успешный JSON-ответ
//    echo json_encode([
//        'status'    => 'ok',
//    ]);
//
//} else {
//
//    # Если путь некорректный, возвращаем ошибку 404
//    http_response_code(404);
//    echo json_encode([
//        'error' => 'Not Found',
//    ]);
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
$metrics = App\Metrics\PrometheusMetrics::getInstance();
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


#          livenessProbe:
#            httpGet:
#              path: /health
#              port: 9000
#            initialDelaySeconds: 5  # Задержка перед первым запуском проверки
#            periodSeconds: 10       # Как часто выполнять проверку
#            timeoutSeconds: 5       # Максимальное время ожидания ответа на запрос
#            failureThreshold: 3     # Количество неудачных попыток, прежде чем контейнер будет перезапущен
#          readinessProbe:
#            httpGet:
#              path: /health
#              port: 9000
#            initialDelaySeconds: 5  # Задержка перед первым запуском проверки
#            periodSeconds: 10       # Как часто выполнять проверку
#            timeoutSeconds: 5       # Максимальное время ожидания ответа на запрос
#            failureThreshold: 3     # Количество неудачных попыток, прежде чем контейнер будет исключен из списка доступных
#        envFrom:
#          - secretRef:
#              name: {{ .Release.Name }}-secrets
#        env:
#          - name: TEST
#            valueFrom:
#             configMapKeyRef:
#              name: {{ .Release.Name }}-configmap
#              key: TEST