<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Service\Notification\Controller\NotificationController;

# Настройки подключения к RabbitMQ
$host = getenv('rabbitmq_host');
$port = getenv('rabbitmq_port');
$user = getenv('rabbitmq_user');
$password = getenv('rabbitmq_password');
$queueName = 'service-notification';

try {

    $connection = new AMQPStreamConnection($host, $port, $user, $password);
    $channel = $connection->channel();

    # Объявляем очередь (устойчивую, если нужно)
    $channel->queue_declare($queueName, false, true, false, false);

    # Callback-функция при получении сообщения
    $callback = function ($msg) {

        try {

            $msg_data = json_decode($msg->body, true);

            if (
                isset($msg_data['action']) and $msg_data['action'] == 'create'
            ) {

                if (isset($msg_data['data']) and sizeof($msg_data['data']) > 0) {

                    # Отправляем запрос в сервис
                    $notification_controller = new NotificationController();
                    $notification = $notification_controller->create($msg_data['data']);

                    $notification_data = json_decode($notification, true);

                    echo " [✓] Оповещение c ID = ".$notification_data['notification_id']."  успешно создано\n";
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