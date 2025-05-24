<?php
use FastRoute\RouteCollector;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {

    # RAGGIT-MQ
    $r->addRoute('GET', '/rabbitmq-send', [\App\Service\Main\Controller\MainController::class, 'rabbitmqSend']);
    $r->addRoute('GET', '/rabbitmq-receive', [\App\Service\Main\Controller\MainController::class, 'rabbitmqReceive']);

    # MAIN
    $r->addRoute('GET', '/', [\App\Service\Main\Controller\MainController::class, 'get']);
    $r->addRoute('GET', '/health', [\App\Service\Health\Controller\HealthController::class, 'get']);
    $r->addRoute('GET', '/api_metrics', [\App\Service\Metric\Controller\MetricController::class, 'get']);

    # Notification
    $r->addRoute('POST', '/notification', [\App\Service\Notification\Controller\NotificationController::class, 'create']);
    $r->addRoute('GET', '/notification', [\App\Service\Notification\Controller\NotificationController::class, 'get']);

    # Order
    $r->addRoute('POST', '/notification', [\App\Service\Order\Controller\OrderController::class, 'create']);
    $r->addRoute('GET', '/notification', [\App\Service\Order\Controller\OrderController::class, 'get']);

    # Auth
    $r->addRoute('POST', '/auth/token', [\App\Service\Auth\Controller\AuthController::class, 'token']);
    $r->addRoute('GET', '/auth/validate', [\App\Service\Auth\Controller\AuthController::class, 'validate']);
    $r->addRoute('POST', '/auth/data', [\App\Service\Auth\Controller\AuthController::class, 'data']);
    $r->addRoute('POST', '/auth/exit', [\App\Service\Auth\Controller\AuthController::class, 'exit']);

    # User - pages
    $r->addRoute('GET', '/user/register', [\App\Service\User\Controller\UserController::class, 'register']);
    $r->addRoute('GET', '/user/login', [\App\Service\User\Controller\UserController::class, 'login']);
    $r->addRoute('POST', '/user/auth', [\App\Service\User\Controller\UserController::class, 'auth']);
    $r->addRoute('GET', '/user/profile', [\App\Service\User\Controller\UserController::class, 'profile']);
    $r->addRoute('GET', '/user/edit', [\App\Service\User\Controller\UserController::class, 'edit']);
    $r->addRoute('GET', '/user/exit', [\App\Service\User\Controller\UserController::class, 'exit']);

    # User - actions
    $r->addRoute('POST', '/user/create', [\App\Service\User\Controller\UserController::class, 'create']);
    $r->addRoute('GET', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'get']);
    $r->addRoute('PUT', '/user/update', [\App\Service\User\Controller\UserController::class, 'update']);
    $r->addRoute('DELETE', '/user/delete', [\App\Service\User\Controller\UserController::class, 'delete']);
    $r->addRoute('POST', '/user/rabbitmq-test', [\App\Service\User\Controller\UserController::class, 'rabbitmqTest']);
});

return $dispatcher;