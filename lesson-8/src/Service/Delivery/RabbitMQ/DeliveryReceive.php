<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Helper\Helper;
###use App\Service\Delivery\Controller\DeliveryController;
use App\Service\Delivery\Model\Delivery;

# Настройки подключения к RabbitMQ
$host = getenv('rabbitmq_host');
$port = getenv('rabbitmq_port');
$user = getenv('rabbitmq_user');
$password = getenv('rabbitmq_password');
$queueName = 'service-delivery';

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

            # Бронирование курьера
            if (isset($msg_data['action']) and $msg_data['action'] == 'create') {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $delivery_model = new Delivery();
                    $delivery = $delivery_model->create($msg_data['data']);

                    $delivery_data = json_decode($delivery, true);

                    echo " [✓] Курьер c ID = ".$delivery_data['delivery_id']." успешно забронирован\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-delivery-receive', '[✓] Курьер c ID = '.$delivery_data['delivery_id'].' успешно забронирован');
                }
            }

            # Освобождение курьера
            if (isset($msg_data['action']) and $msg_data['action'] == 'delete') {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $delivery_model = new Delivery();
                    $delivery = $delivery_model->delete($msg_data['data']['data']);

                    $delivery_data = json_decode($delivery, true);

                    echo " [✓] Курьер c ID = ".$delivery_data['delivery_id']." успешно освобожден\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓] Курьер c ID = '.$delivery_data['delivery_id'].' успешно освобожден');
                }
            }

            # [SAGA]
            if (isset($msg_data['type']) and $msg_data['type'] == 'saga') {

                $helper->setNotification($msg_data['data']['user_id'], 'saga_delivery_body', $msg->body);

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $delivery_model = new Delivery();

                    # Бронирование курьера
                    if ($msg_data['data']['action'] == 'create') {

                        $delivery = $delivery_model->create($msg_data['data']);

                        echo " [✓] Курьер c ID = ".$delivery['delivery_id']." успешно забронирован\n";

                        /* {{{ */
                            # Возвращаем ответ, что сумма заказа успешно снята

                            # Данные для отправки
                            $data_order = [
                                'type' => 'saga',
                                'data' => [
                                    'action' => 'billing',
                                    'user_id' => $msg_data['data']['user_id'],
                                    'order_id' => $msg_data['data']['order_id'],
                                    'order_saga_id' => $msg_data['data']['order_saga_id'],
                                    'amount' => $msg_data['data']['amount'],
                                    'warehouse_list' => $msg_data['data']['warehouse_list'],
                                    'billing_action_id' => $msg_data['data']['billing_action_id'],
                                    'delivery_action_id' => $delivery['delivery_action_id'],
                                    'status' => 'ok'
                                ]
                            ];

                            # Отправляем сообщение в RabbitMQ
                            $helper->rabbitmqSend('service-order', json_encode($data_order));
                        /* }}} */

                        $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓][SAGA] Сумма биллингового аккаунта успешно уменьшена на '.$msg_data['data']['amount']);
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
                            'action' => 'delivery',
                            'user_id' => $msg_data['data']['user_id'],
                            'order_id' => $msg_data['data']['order_id'],
                            'order_saga_id' => $msg_data['data']['order_saga_id'],
                            'amount' => $msg_data['data']['amount'],
                            'warehouse_list' => $msg_data['data']['warehouse_list'],
                            'status' => 'error',
                            'error_text' => $e->getMessage()
                        ]
                    ];

                    # Отправляем сообщение в RabbitMQ
                    $helper->rabbitmqSend('service-order', json_encode($data_order));
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