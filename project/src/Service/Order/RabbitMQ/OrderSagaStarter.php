<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use App\Helper\Helper;
use App\Service\Order\Model\Order;

# Оповещения
$helper = new Helper();

try {

    echo " [*] Ожидание заказов. Для выхода нажмите Ctrl+C\n";

    // Бесконечный цикл проверки сообщений
    while (true) {

        try {

            # Отправляем запрос в сервис
            $order_model = new Order();
            $order_saga_list = $order_model->getSagaForStartList();

            if (sizeof($order_saga_list) > 0) {

                foreach ($order_saga_list as $order_saga) {

                    /* {{{ */
                    # Уменьшаем сумму биллинг аккаунта

                    # Данные для отправки
                    $data_billing = [
                        'type' => 'saga',
                        'data' => [
                            'action' => 'minus',
                            'user_id' => $order_saga['user_id'],
                            'order_id' => $order_saga['order_id'],
                            'order_saga_id' => $order_saga['order_saga_id'],
                            'amount' => $order_saga['amount'],
                            'warehouse_list' => $order_saga['warehouse_list']
                        ]
                    ];

                    # Обновляем статус SAGA
                    $order_model->updateSaga($order_saga['order_saga_id'], 'status', 3);

                    # Отправляем сообщение в RabbitMQ
                    $helper->rabbitmqSend('service-billing', json_encode($data_billing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
                    /* }}} */

                    # Добавляем оповещение
                    $helper->setNotification($order['user_id'], 'create_order_ok', '[✓] Заказ на сумму '.$order['amount'].' c ID = '.$order['order_id'].' успешно создан');
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