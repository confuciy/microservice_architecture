<?php
use FastRoute\RouteCollector;
//use App\Controller\UserController;
//use App\Controller\HealthController;
//use App\Metrics\PrometheusMetrics;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/health', [\App\Controller\HealthController::class, 'getHealth']);
    $r->addRoute('POST', '/user', [\App\Controller\UserController::class, 'createUser']);
    $r->addRoute('GET', '/user/{userId:\d+}', [\App\Controller\UserController::class, 'getUser']);
    $r->addRoute('DELETE', '/user/{userId:\d+}', [\App\Controller\UserController::class, 'deleteUser']);
    $r->addRoute('PUT', '/user/{userId:\d+}', [\App\Controller\UserController::class, 'updateUser']);
    $r->addRoute('GET', '/metrics', [\App\Controller\MetricsController::class, 'getMetrics']);
//    $r->addRoute('GET', '/metrics', '');
//    $r->addRoute('GET', '/metrics', function () {
//        $metrics = App\Metrics\PrometheusMetrics::getInstance();
//        header('Content-Type: text/plain');
//        echo $metrics->renderMetrics();
//    });
});

return $dispatcher;