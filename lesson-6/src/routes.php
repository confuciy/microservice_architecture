<?php
use FastRoute\RouteCollector;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {

    # MAIN
    $r->addRoute('GET', '/', [\App\Service\Main\Controller\MainController::class, 'get']);
    $r->addRoute('GET', '/health', [\App\Service\Health\Controller\HealthController::class, 'get']);
    $r->addRoute('GET', '/api_metrics', [\App\Service\Metrics\Controller\MetricsController::class, 'get']);

    # Notification
    $r->addRoute('POST', '/notification', [\App\Service\Notification\Controller\NotificationController::class, 'create']);
    $r->addRoute('GET', '/notification', [\App\Service\Notification\Controller\NotificationController::class, 'get']);

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
    $r->addRoute('GET', '/user/exit', [\App\Service\User\Controller\UserController::class, 'exit']);

    # User - actions
    $r->addRoute('POST', '/user/create', [\App\Service\User\Controller\UserController::class, 'create']);
    $r->addRoute('GET', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'get']);
    $r->addRoute('PUT', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'update']);
    $r->addRoute('DELETE', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'delete']);
});

return $dispatcher;