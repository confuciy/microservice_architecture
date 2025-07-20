<?php
use FastRoute\RouteCollector;

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {

    # MAIN
    $r->addRoute('GET', '/', [\App\Service\Main\Controller\MainController::class, 'get']);
    $r->addRoute('GET', '/health', [\App\Service\Health\Controller\HealthController::class, 'get']);
    $r->addRoute('GET', '/api_metrics', [\App\Service\Metric\Controller\MetricController::class, 'get']);

    # Notification
    $r->addRoute('POST', '/notification', [\App\Service\Notification\Controller\NotificationController::class, 'create']);
    $r->addRoute('GET', '/notification', [\App\Service\Notification\Controller\NotificationController::class, 'get']);

    # Billing
    $r->addRoute('POST', '/billing', [\App\Service\Billing\Controller\BillingController::class, 'create']);
    $r->addRoute('GET', '/billing', [\App\Service\Billing\Controller\BillingController::class, 'get']);
    $r->addRoute('POST', '/billing/amount', [\App\Service\Billing\Controller\BillingController::class, 'amount']);

    # Warehouse
    $r->addRoute('POST', '/warehouse', [\App\Service\Warehouse\Controller\WarehouseController::class, 'create']);
    $r->addRoute('GET', '/warehouse', [\App\Service\Warehouse\Controller\WarehouseController::class, 'get']);
    $r->addRoute('POST', '/warehouse/order', [\App\Service\Warehouse\Controller\WarehouseController::class, 'order']);

    # Delivery
    $r->addRoute('POST', '/delivery', [\App\Service\Delivery\Controller\DeliveryController::class, 'create']);
    $r->addRoute('GET', '/delivery', [\App\Service\Delivery\Controller\DeliveryController::class, 'get']);
    $r->addRoute('POST', '/delivery/order', [\App\Service\Delivery\Controller\DeliveryController::class, 'order']);
    $r->addRoute('POST', '/delivery/delivered', [\App\Service\Delivery\Controller\DeliveryController::class, 'delivered']);

    # Order
    $r->addRoute('POST', '/order', [\App\Service\Order\Controller\OrderController::class, 'create']);
    $r->addRoute('GET', '/order', [\App\Service\Order\Controller\OrderController::class, 'get']);
    $r->addRoute('POST', '/order/check', [\App\Service\Order\Controller\OrderController::class, 'check']);
    $r->addRoute('POST', '/order/order', [\App\Service\Order\Controller\OrderController::class, 'order']);

    # Auth
    $r->addRoute('POST', '/auth/token', [\App\Service\Auth\Controller\AuthController::class, 'token']);
    $r->addRoute('GET', '/auth/validate', [\App\Service\Auth\Controller\AuthController::class, 'validate']);
    $r->addRoute('POST', '/auth/data', [\App\Service\Auth\Controller\AuthController::class, 'data']);
    $r->addRoute('POST', '/auth/exit', [\App\Service\Auth\Controller\AuthController::class, 'exit']);

    # User - pages
    $r->addRoute('GET', '/user/register', [\App\Service\User\Controller\UserController::class, 'register']);
    $r->addRoute('GET', '/user/notification', [\App\Service\User\Controller\UserController::class, 'notification']);
    $r->addRoute('GET', '/user/order', [\App\Service\User\Controller\UserController::class, 'order']);
    $r->addRoute('GET', '/user/billing', [\App\Service\User\Controller\UserController::class, 'billing']);
    $r->addRoute('GET', '/user/login', [\App\Service\User\Controller\UserController::class, 'login']);
    $r->addRoute('POST', '/user/auth', [\App\Service\User\Controller\UserController::class, 'auth']);
    $r->addRoute('GET', '/user/profile', [\App\Service\User\Controller\UserController::class, 'profile']);
    $r->addRoute('GET', '/user/edit', [\App\Service\User\Controller\UserController::class, 'edit']);
    $r->addRoute('GET', '/user/exit', [\App\Service\User\Controller\UserController::class, 'exit']);

    # User - actions
    $r->addRoute('POST', '/user/create', [\App\Service\User\Controller\UserController::class, 'create']);
    $r->addRoute('GET', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'get']);
    $r->addRoute('PUT', '/user/{userId:\d+}', [\App\Service\User\Controller\UserController::class, 'update']);
    $r->addRoute('POST', '/user/update', [\App\Service\User\Controller\UserController::class, 'update']);
    $r->addRoute('DELETE', '/user/delete', [\App\Service\User\Controller\UserController::class, 'delete']);
});

return $dispatcher;