<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use App\Helper\Helper;
use App\Service\Order\Model\Order;

# Оповещения
$helper = new Helper();

try {

    echo " [*] Ожидание заказов. Для выхода нажмите Ctrl+C\n";

    # Отправляем запрос в сервис
    $order_model = new Order();

    // Бесконечный цикл проверки сообщений
    while (true) {

        try {

            $order_saga_list = $order_model->getSagaForStartList();

            if (sizeof($order_saga_list) > 0) {

                foreach ($order_saga_list as $order_saga) {

                    # Обновляем статус SAGA
                    $order_model->updateSaga($order_saga['order_saga_id'], 'status', 3);

                    # Отправляем сообщение в RabbitMQ
                    $helper->rabbitmqSend('service-billing', $order_saga['message']);
                }
            }

        } catch (Exception $e) {

            echo " [✗] Ошибка в основном цикле: ", $e->getMessage(), "\n";
        }

        sleep(5); // Пауза между проверками
    }

} catch (Exception $e) {

    echo " [✗] Критическая ошибка: ", $e->getMessage(), "\n";

    # Выход с кодом ошибки для перезапуска через supervisord
    exit(1);
}