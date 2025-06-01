<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Helper\Helper;
use App\Service\Billing\Controller\BillingController;

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

        ###$helper->setNotification(0, 'rabbitmq-billing-receive', 'Callback-функция при получении сообщения');

        try {

            $msg_data = json_decode($msg->body, true);

            if (
                isset($msg_data['action']) and $msg_data['action'] == 'create'
            ) {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $billing_controller = new BillingController();
                    $billing = $billing_controller->create($msg_data['data']);

                    $billing_data = json_decode($billing, true);

                    echo " [✓] Биллинговый аккаунт c ID = ".$billing_data['billing_id']."  успешно создан\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓] Биллинговый аккаунт c ID = '.$billing_data['billing_id'].' успешно создан');
                }
            }

            if (
                isset($msg_data['action']) and $msg_data['action'] == 'plus'
            ) {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    $msg_data['data']['action'] = $msg_data['action'];

                    # Отправляем запрос в сервис
                    $billing_controller = new BillingController();
                    $billing = $billing_controller->amount($msg_data['data']);

                    ###$billing_data = json_decode($billing, true);

                    echo " [✓] Сумма биллингового аккаунта успешно пополнена на ".$msg_data['data']['amount']."\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', 'Сумма биллингового аккаунта успешно пополнена на '.$msg_data['data']['amount']);
                }
            }

            if (
                isset($msg_data['action']) and $msg_data['action'] == 'minus'
            ) {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    $msg_data['data']['action'] = $msg_data['action'];

                    # Отправляем запрос в сервис
                    $billing_controller = new BillingController();
                    $billing = $billing_controller->amount($msg_data['data']);

                    ###$billing_data = json_decode($billing, true);

                    echo " [✓] Сумма биллингового аккаунта успешно уменьшена на ".$msg_data['data']['amount']."\n";

                    $helper->setNotification($msg_data['data']['user_id'], 'rabbitmq-billing-receive', '[✓] Сумма биллингового аккаунта успешно уменьшена на '.$msg_data['data']['amount']);
                }
            }

            # Подтверждаем только после успешной обработки
            $msg->ack();

            ###$helper->setNotification(0, 'rabbitmq-billing-receive', '[✓] Получено: '.$msg->body);

        } catch (Exception $e) {

            echo " [✗] Ошибка при отправке: ", $e->getMessage(), "\n";

            ###$helper->setNotification(0, 'rabbitmq-billing-receive', '[✗] Ошибка при отправке: '.$e->getMessage());

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

    ###$helper->setNotification(1, 'rabbitmq-receive', 'Критическая ошибка: '.$e->getMessage());

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