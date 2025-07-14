<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Helper\Helper;
###use App\Service\Order\Controller\OrderController;
use App\Service\Order\Model\Order;

# Настройки подключения к RabbitMQ
$host = getenv('rabbitmq_host');
$port = getenv('rabbitmq_port');
$user = getenv('rabbitmq_user');
$password = getenv('rabbitmq_password');
$queueName = 'service-order';

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

            if (isset($msg_data['action']) and $msg_data['action'] == 'create') {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $order_model = new Order();
                    $order = $order_model->create($msg_data['data']);

                    $order_data = json_decode($order, true);

                    echo " [✓] Заказ c ID = ".$order_data['order_id']." успешно создан\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✓] Заказ c ID = '.$order_data['order_id'].' успешно создан');
                }
            }

            # [SAGA]
            if (isset($msg_data['type']) and $msg_data['type'] == 'saga') {

                $helper->setNotification($msg_data['data']['user_id'], 'saga_order_body', $msg->body);

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $order_model = new Order();



                    # [BILLING]

                    # Транзакция
                    if (isset($msg_data['data']['action']) and $msg_data['data']['action'] == 'billing') {

                        # Успешно
                        if ($msg_data['data']['status'] == 'ok') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'billing_action_id', $msg_data['data']['billing_action_id']);
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'billing_status', 1);

                            # Обновляем заказ - статус "Оплачен"
                            $order_model->updateOrder($msg_data['data']['order_id'], 'status', 2);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✓] Заказ c ID = '.$msg_data['data']['order_id'].' успешно оплачен');

                            /* {{{ */
                                # Бронируем товары на складе

                                # Данные для отправки
                                $data_warehouse = [
                                    'type' => 'saga',
                                    'data' => [
                                        'action' => 'minus',
                                        'user_id' => $msg_data['data']['user_id'],
                                        'order_id' => $msg_data['data']['order_id'],
                                        'order_saga_id' => $msg_data['data']['order_saga_id'],
                                        'amount' => $msg_data['data']['amount'],
                                        'warehouse_list' => $msg_data['data']['warehouse_list'],
                                        'billing_action_id' => $msg_data['data']['billing_action_id']
                                    ]
                                ];

                                # Отправляем сообщение в RabbitMQ
                                $helper->rabbitmqSend('service-warehouse', json_encode($data_warehouse));
                            /* }}} */

                        }

                        # Ошибка
                        if ($msg_data['data']['status'] == 'error') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'billing_status', 2);

                            # Обновляем заказ - статус "Отменен"
                            $order_model->updateOrder($msg_data['data']['order_id'], 'status', 6);

                            # Обновляем заказ - текст ошибки заказа
                            $order_model->updateOrder($msg_data['data']['order_id'], 'error_text', $msg_data['data']['error_text']);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✗] Заказ c ID = '.$msg_data['data']['order_id'].' отменен');
                        }
                    }

                    # Компенсационная транзакция
                    if ($msg_data['data']['action'] == 'billing_compensation') {

                        # Успешно
                        if ($msg_data['data']['status'] == 'ok') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'billing_status', 2);

                            # Обновляем заказ - статус "Отменен"
                            $order_model->updateOrder($msg_data['data']['order_id'], 'status', 6);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✗] Заказ c ID = '.$msg_data['data']['order_id'].' отменен, средства возвращены');
                        }
                    }



                    # [WAREHOUSE]

                    # Транзакция
                    if (isset($msg_data['data']['action']) and $msg_data['data']['action'] == 'warehouse') {

                        # Успешно
                        if ($msg_data['data']['status'] == 'ok') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'warehouse_status', 1);

                            # Обновляем заказ - статус "Товары зарезервированы"
                            $order_model->updateOrder($msg_data['data']['order_id'], 'status', 3);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✓] Товар на складе для заказа c ID = '.$msg_data['data']['order_id'].' успешно зарезервирован');

                            /* {{{ */
                                # Бронируем курьера

                                # Данные для отправки
                                $data_warehouse = [
                                    'type' => 'saga',
                                    'data' => [
                                        'action' => 'minus',
                                        'user_id' => $msg_data['data']['user_id'],
                                        'order_id' => $msg_data['data']['order_id'],
                                        'order_saga_id' => $msg_data['data']['order_saga_id'],
                                        'amount' => $msg_data['data']['amount'],
                                        'warehouse_list' => $msg_data['data']['warehouse_list'],
                                        'billing_action_id' => $msg_data['data']['billing_action_id']
                                    ]
                                ];

                                # Отправляем сообщение в RabbitMQ
                                $helper->rabbitmqSend('service-delivery', json_encode($data_warehouse));
                            /* }}} */

                        }

                        # Ошибка
                        if ($msg_data['data']['status'] == 'error') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'warehouse_status', 2);

                            # Обновляем заказ - текст ошибки заказа
                            $order_model->updateOrder($msg_data['data']['order_id'], 'error_text', $msg_data['data']['error_text']);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✗] Бронирование товаров на складе для заказа c ID = '.$msg_data['data']['order_id'].' отменено');

                            /* {{{ */
                                # [Компенсационная транзакция]

                                # Удаление резерва товара

                                # Данные для отправки
                                $data_warehouse = [
                                    'type' => 'saga',
                                    'data' => [
                                        'action' => 'plus',
                                        'user_id' => $msg_data['data']['user_id'],
                                        'order_id' => $msg_data['data']['order_id'],
                                        'order_saga_id' => $msg_data['data']['order_saga_id'],
                                        'amount' => $msg_data['data']['amount'],
                                        'warehouse_list' => $msg_data['data']['warehouse_list'],
                                        'billing_action_id' => $msg_data['data']['billing_action_id']
                                    ]
                                ];

                                # Отправляем сообщение в RabbitMQ
                                $helper->rabbitmqSend('service-billing', json_encode($data_warehouse));
                            /* }}} */
                        }
                    }

                    # Компенсационная транзакция
                    if ($msg_data['data']['action'] == 'warehouse_compensation') {

                        # Успешно
                        if ($msg_data['data']['status'] == 'ok') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'warehouse_status', 2);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✗] Бронирование товаров на складе для заказа c ID = '.$msg_data['data']['order_id'].' отменено');

//                            # Обновляем заказ - статус "Отменен"
//                            $order_model->updateSaga($msg_data['data']['order_id'], 'status', 6);

                            /* {{{ */
                                # [Компенсационная транзакция]

                                # Удаление резерва товара

                                # Данные для отправки
                                $data_billing = [
                                    'type' => 'saga',
                                    'data' => [
                                        'action' => 'plus',
                                        'user_id' => $msg_data['data']['user_id'],
                                        'order_id' => $msg_data['data']['order_id'],
                                        'order_saga_id' => $msg_data['data']['order_saga_id'],
                                        'amount' => $msg_data['data']['amount'],
                                        'warehouse_list' => $msg_data['data']['warehouse_list'],
                                        'billing_action_id' => $msg_data['data']['billing_action_id']
                                    ]
                                ];

                                # Отправляем сообщение в RabbitMQ
                                $helper->rabbitmqSend('service-billing', json_encode($data_billing));
                            /* }}} */
                        }
                    }



                    # [DELIVERY]

                    # Транзакция
                    if (isset($msg_data['data']['action']) and $msg_data['data']['action'] == 'delivery') {

                        # Успешно
                        if ($msg_data['data']['status'] == 'ok') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'delivery_action_id', $msg_data['data']['delivery_action_id']);
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'delivery_status', 1);
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'status', 1);

                            # Обновляем заказ - статус "Ожидает доставку"
                            $order_model->updateOrder($msg_data['data']['order_id'], 'status', 4);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✓] Товары заказа c ID = '.$msg_data['data']['order_id'].' ожидают доставку');
                        }

                        # Ошибка
                        if ($msg_data['data']['status'] == 'error') {

                            # Обновляем SAGA
                            $order_model->updateSaga($msg_data['data']['order_saga_id'], 'delivery_status', 2);

                            # Обновляем заказ - текст ошибки заказа
                            $order_model->updateOrder($msg_data['data']['order_id'], 'error_text', $msg_data['data']['error_text']);

                            $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✗] Доставка товаров для заказа c ID = '.$msg_data['data']['order_id'].' отменена');

                            /* {{{ */
                                # [Компенсационная транзакция]

                                # Удаление резерва товара

                                # Данные для отправки
                                $data_warehouse = [
                                    'type' => 'saga',
                                    'data' => [
                                        'action' => 'plus',
                                        'user_id' => $msg_data['data']['user_id'],
                                        'order_id' => $msg_data['data']['order_id'],
                                        'order_saga_id' => $msg_data['data']['order_saga_id'],
                                        'amount' => $msg_data['data']['amount'],
                                        'warehouse_list' => $msg_data['data']['warehouse_list'],
                                        'billing_action_id' => $msg_data['data']['billing_action_id']
                                    ]
                                ];

                                # Отправляем сообщение в RabbitMQ
                                $helper->rabbitmqSend('service-warehouse', json_encode($data_warehouse));
                            /* }}} */
                        }
                    }

//                    # Товар доставлен
//                    if ($msg_data['data']['status'] == 'delivered') {
//
//                        # Обновляем заказ - статус "Доставлен"
//                        $order_model->updateSaga($msg_data['data']['order_id'], 'status', 5);
//                    }
                }
            }

            if (isset($msg_data['action']) and $msg_data['action'] == 'delivered') {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $order_model = new Order();

                    # Обновляем заказ - статус "Доставлен"
                    $order_model->updateOrder($msg_data['data']['order_id'], 'status', 5);

                    echo " [✓] Заказ c ID = ".$msg_data['data']['order_id']." успешно доставлен\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-order-receive', '[✓] Заказ c ID = '.$msg_data['data']['order_id'].' успешно доставлен');
                }
            }

            # Подтверждаем только после успешной обработки
            $msg->ack();

        } catch (Exception $e) {

            echo " [✗] Ошибка при отправке: ", $e->getMessage(), "\n";

            # Отказываемся от сообщения с requeue=true
            $msg->nack(false, true);
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