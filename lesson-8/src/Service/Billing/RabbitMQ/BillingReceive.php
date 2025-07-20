<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Helper\Helper;
###use App\Service\Billing\Controller\BillingController;
use App\Service\Billing\Model\Billing;

# Настройки подключения к RabbitMQ
$host = getenv('rabbitmq_host');
$port = getenv('rabbitmq_port');
$user = getenv('rabbitmq_user');
$password = getenv('rabbitmq_password');
$queueName = 'service-billing';

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

            # Создание биллингового аккаунта
            if (isset($msg_data['action']) and $msg_data['action'] == 'create') {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $billing_model = new Billing();
                    $billing = $billing_model->create($msg_data['data']);

                    $billing_data = json_decode($billing, true);

                    echo " [✓] Биллинговый аккаунт c ID = ".$billing_data['billing_id']."  успешно создан\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓] Биллинговый аккаунт c ID = '.$billing_data['billing_id'].' успешно создан');
                }
            }

            # Увеличение суммы биллингового аккаунта
            if (isset($msg_data['action']) and $msg_data['action'] == 'plus') {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    $msg_data['data']['action'] = $msg_data['action'];

                    # Отправляем запрос в сервис
                    $billing_model = new Billing();
                    $billing_model->amount($msg_data['data']);

                    echo " [✓] Сумма биллингового аккаунта успешно пополнена на ".$msg_data['data']['amount']."\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓] Сумма биллингового аккаунта успешно пополнена на '.$msg_data['data']['amount']);
                }
            }

            # Уменьшение суммы биллингового аккаунта
            if (isset($msg_data['action']) and $msg_data['action'] == 'minus') {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    $msg_data['data']['action'] = $msg_data['action'];

                    # Отправляем запрос в сервис
                    $billing_model = new Billing();
                    $billing_model->amount($msg_data['data']);

                    echo " [✓] Сумма биллингового аккаунта успешно уменьшена на ".$msg_data['data']['amount']."\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓] Сумма биллингового аккаунта успешно уменьшена на '.$msg_data['data']['amount']);
                }
            }

            # [SAGA]
            if (isset($msg_data['type']) and $msg_data['type'] == 'saga') {

                ###$helper->setNotification($msg_data['data']['user_id'], 'saga_billing_body', $msg->body);

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $billing_model = new Billing();

                    # MINUS
                    if ($msg_data['data']['action'] == 'minus') {

                        ###$helper->setNotification($msg_data['data']['user_id'], 'order_saga_minus', $msg->body);

                        $billing = $billing_model->amount($msg_data['data']);

                        echo " [✓][SAGA] Сумма биллингового аккаунта пользователя с ID = ".$msg_data['data']['user_id']." успешно уменьшена на ".$msg_data['data']['amount']."\n";

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
                                    'billing_action_id' => $billing['billing_action']['billing_action_id'],
                                    'status' => 'ok'
                                ]
                            ];

                            # Отправляем сообщение в RabbitMQ
                            $helper->rabbitmqSend('service-order', json_encode($data_order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
                        /* }}} */

                        $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓][SAGA] Сумма биллингового аккаунта пользователя с ID = '.$msg_data['data']['user_id'].' успешно уменьшена на '.$msg_data['data']['amount']);
                    }

                    # PLUS
                    if ($msg_data['data']['action'] == 'plus') {

                        $billing = $billing_model->amount($msg_data['data']);

                        echo " [✓][SAGA] Сумма биллингового аккаунта пользователя с ID = ".$msg_data['data']['user_id']." успешно пополнена на ".$msg_data['data']['amount']."\n";

                        /* {{{ */
                            # Возвращаем ответ, что сумма заказа успешно снята

                            # Данные для отправки
                            $data_order = [
                                'type' => 'saga',
                                'data' => [
                                    'action' => 'billing_compensation',
                                    'user_id' => $msg_data['data']['user_id'],
                                    'order_id' => $msg_data['data']['order_id'],
                                    'order_saga_id' => $msg_data['data']['order_saga_id'],
                                    'amount' => $msg_data['data']['amount'],
                                    'warehouse_list' => $msg_data['data']['warehouse_list'],
                                    'billing_action_id' => $billing['billing_action']['billing_action_id'],
                                    'status' => 'ok'
                                ]
                            ];

                            # Отправляем сообщение в RabbitMQ
                            $helper->rabbitmqSend('service-order', json_encode($data_order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
                        /* }}} */

                        $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓][SAGA] Сумма биллингового аккаунта пользователя с ID = '.$msg_data['data']['user_id'].' успешно пополнена на '.$msg_data['data']['amount']);
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
                            'action' => 'billing',
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