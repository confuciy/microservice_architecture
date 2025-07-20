<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Helper\Helper;
###use App\Service\Warehouse\Controller\WarehouseController;
use App\Service\Warehouse\Model\Warehouse;

# Настройки подключения к RabbitMQ
$host = getenv('rabbitmq_host');
$port = getenv('rabbitmq_port');
$user = getenv('rabbitmq_user');
$password = getenv('rabbitmq_password');
$queueName = 'service-warehouse';

# Оповещения
$helper = new Helper();

try {

    $connection = new AMQPStreamConnection($host, $port, $user, $password);
    $channel = $connection->channel();

    # Объявляем очередь (устойчивую, если нужно)
    $channel->queue_declare($queueName, false, true, false, false);

    # Callback-функция при получении сообщения
    $callback = function ($msg) use ($helper) {

        try {

            $msg_data = json_decode($msg->body, true);

//            if (
//                isset($msg_data['action']) and $msg_data['action'] == 'create'
//            ) {
//
//                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {
//
//                    # Отправляем запрос в сервис
//                    $warehouse_model = new Warehouse();
//                    $warehouse = $warehouse_model->create($msg_data['data']);
//
//                    $warehouse_data = json_decode($warehouse, true);
//
//                    echo " [✓] Бронирование на складе c ID = ".$warehouse_data['warehouse_id']." успешно создано\n";
//
//                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-warehouse-receive', '[✓] Бронирование на складе c ID = '.$warehouse_data['warehouse_id'].' успешно создано');
//                }
//            }

            # [SAGA]
            if (isset($msg_data['type']) and $msg_data['type'] == 'saga') {

                ###$helper->setNotification($msg_data['data']['user_id'], 'saga_warehouse_body', $msg->body);

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $warehouse_model = new Warehouse();

                    # MINUS
                    if ($msg_data['data']['action'] == 'minus') {

                        # Проверка достаточного кол-ва товара на складе
                        if ($warehouse_model->checkWarehouseListCount($msg_data['data']) == 0) {

                            # Резервируем товар
                            $data = $warehouse_model->create($msg_data['data']);

                            echo " [✓][SAGA] Резервируем товар на складе для заказа ID = ".$msg_data['data']['order_id']."\n";


                            /* {{{ */
                                # Возвращаем ответ, что сумма заказа успешно снята

                                # Данные для отправки
                                $data_order = [
                                    'type' => 'saga',
                                    'data' => [
                                        'action' => 'warehouse',
                                        'user_id' => $msg_data['data']['user_id'],
                                        'order_id' => $msg_data['data']['order_id'],
                                        'order_saga_id' => $msg_data['data']['order_saga_id'],
                                        'amount' => $msg_data['data']['amount'],
                                        'warehouse_list' => $msg_data['data']['warehouse_list'],
                                        'warehouse_action_list' => $data['warehouse_action_list'],
                                        'billing_action_id' => $msg_data['data']['billing_action_id'],
                                        'status' => 'ok'
                                    ]
                                ];

                                # Отправляем сообщение в RabbitMQ
                                $helper->rabbitmqSend('service-order', json_encode($data_order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
                            /* }}} */

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-warehouse-receive', '[✓][SAGA] Резервируем товар на складе для заказа ID = '.$msg_data['data']['order_id']);

                        } else {

                            # Резервируем товар
                            $data = $warehouse_model->create($msg_data['data']);

                            # Удаляем резер товара
                            $data = $warehouse_model->delete($msg_data['data']);

                            throw new \Exception('На складе недостаточно товара');
                        }
                    }

                    # PLUS
                    if ($msg_data['data']['action'] == 'plus') {

                        # Удаляем зарезервированный товар
                        $warehouse_model->delete($msg_data['data']);

                        echo " [✓] Резерв товара на складе успешно удален\n";

                        /* {{{ */
                            # Возвращаем ответ, что сумма заказа успешно снята

                            # Данные для отправки
                            $data_order = [
                                'type' => 'saga',
                                'data' => [
                                    'action' => 'warehouse_compensation',
                                    'user_id' => $msg_data['data']['user_id'],
                                    'order_id' => $msg_data['data']['order_id'],
                                    'order_saga_id' => $msg_data['data']['order_saga_id'],
                                    'amount' => $msg_data['data']['amount'],
                                    'warehouse_list' => $msg_data['data']['warehouse_list'],
                                    'warehouse_action_list' => $data['warehouse_action_list'],
                                    'billing_action_id' => $msg_data['data']['billing_action_id'],
                                    'status' => 'ok'
                                ]
                            ];

                            # Отправляем сообщение в RabbitMQ
                            $helper->rabbitmqSend('service-order', json_encode($data_order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
                        /* }}} */

                        $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-warehouse-receive', '[✓][SAGA] Резерв товара на складе успешно удален');
                    }
                }
            }

            # Подтверждаем только после успешной обработки
            $msg->ack();

        } catch (Exception $e) {

            echo " [✗] Ошибка при отправке: ", $e->getMessage(), "\n";

            # Отказываемся от сообщения с requeue=true
            $msg->nack(false, true);

            # [SAGA]
            if (isset($msg_data['type']) and $msg_data['type'] == 'saga') {

                /* {{{ */
                    # Возвращаем ответ, что произошла ошибка

                    # Данные для отправки
                    $data_order = [
                        'type' => 'saga',
                        'data' => [
                            'action' => 'warehouse',
                            'user_id' => $msg_data['data']['user_id'],
                            'order_id' => $msg_data['data']['order_id'],
                            'order_saga_id' => $msg_data['data']['order_saga_id'],
                            'amount' => $msg_data['data']['amount'],
                            'warehouse_list' => $msg_data['data']['warehouse_list'],
                            'billing_action_id' => $msg_data['data']['billing_action_id'],
                            'status' => 'error',
                            'error_text' => $e->getMessage()
                        ]
                    ];

                    # Отправляем сообщение в RabbitMQ
                    $helper->rabbitmqSend('service-order', json_encode($data_order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
                /* }}} */
            }
        }
    };

    # Подписываемся с no_ack=false (важно!)
    $channel->basic_consume(
        $queueName,
        '',
        false, # no_local
        false, # no_ack - ДОЛЖНО БЫТЬ false для ручного ack/nack
        false, # exclusive
        false, # nowait
        $callback
    );

    echo " [*] Ожидание сообщений. Для выхода нажмите Ctrl+C\n";

    # Основной цикл обработки
    while ($channel->is_open()) {

        try {

            $channel->wait();

        } catch (Exception $e) {

            echo " [✗] Ошибка в основном цикле: ", $e->getMessage(), "\n";

            # Можно добавить задержку перед повторной попыткой
            sleep(5);
        }
    }

} catch (Exception $e) {

    echo " [✗] Критическая ошибка: ", $e->getMessage(), "\n";

    # Попытка корректно закрыть соединение при ошибке
    if (isset($channel)) {
        $channel->close();
    }
    if (isset($connection)) {
        $connection->close();
    }

    # Выход с кодом ошибки для перезапуска через supervisord
    exit(1);
}