<?php

# Устанавливаем заголовок контента на application/json
header('Content-Type: application/json');

# Получаем текущий путь
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestPath = rtrim($requestPath, '/');

# Проверяем, соответствует ли путь /health/
if ($requestPath === '/health') {

    # Для проверки обращения к разным подам
    #$hostname = gethostname();

    # Возвращаем успешный JSON-ответ
    echo json_encode([
        'status'    => 'ok',
        #"replica"   => $hostname
    ]);

} elseif (preg_match('/^\/otusapp\/(.*)\/health$/', $requestPath)) {

    # Возвращаем успешный JSON-ответ
    echo json_encode([
        'status'    => 'ok',
    ]);

} else {

    # Если путь некорректный, возвращаем ошибку 404
    http_response_code(404);
    echo json_encode([
        'error' => 'Not Found',
    ]);
}