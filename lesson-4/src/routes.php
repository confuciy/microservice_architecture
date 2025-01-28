<?php
use FastRoute\RouteCollector;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/health', [\App\Controller\HealthController::class, 'getHealth']);
    $r->addRoute('POST', '/user', [\App\Controller\UserController::class, 'createUser']);
    $r->addRoute('GET', '/user/{userId:\d+}', [\App\Controller\UserController::class, 'getUser']);
    $r->addRoute('DELETE', '/user/{userId:\d+}', [\App\Controller\UserController::class, 'deleteUser']);
    $r->addRoute('PUT', '/user/{userId:\d+}', [\App\Controller\UserController::class, 'updateUser']);
});

return $dispatcher;