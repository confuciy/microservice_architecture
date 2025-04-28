<?php
//use FastRoute\RouteCollector;
//
//$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
//    $r->addRoute('GET', '/health', [\User\Controller\HealthController::class, 'getHealth']);
//    $r->addRoute('POST', '/user', [\User\Controller\UserController::class, 'createUser']);
//    $r->addRoute('GET', '/user/{userId:\d+}', [\User\Controller\UserController::class, 'getUser']);
//    $r->addRoute('DELETE', '/user/{userId:\d+}', [\User\Controller\UserController::class, 'deleteUser']);
//    $r->addRoute('PUT', '/user/{userId:\d+}', [\User\Controller\UserController::class, 'updateUser']);
//});
//
//return $dispatcher;

use FastRoute\RouteCollector;
//use App\Controller\UserController;
//use App\Controller\HealthController;
//use App\Metrics\PrometheusMetrics;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/health', [\App\Service\Health\Controller\HealthController::class, 'getHealth']);
    $r->addRoute('POST', '/user', [\App\Service\User\Controller\UserController::class, 'createUser']);
    $r->addRoute('GET', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'getUser']);
    $r->addRoute('DELETE', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'deleteUser']);
    $r->addRoute('PUT', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'updateUser']);
    $r->addRoute('GET', '/api_metrics', [\App\Service\Metrics\Controller\MetricsController::class, 'getMetrics']);
//    $r->addRoute('GET', '/metrics', '');
//    $r->addRoute('GET', '/metrics', function () {
//        $metrics = App\Metrics\PrometheusMetrics::getInstance();
//        header('Content-Type: text/plain');
//        echo $metrics->renderMetrics();
//    });
});

return $dispatcher;