<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use GuzzleHttp\Client;
use App\Helper\Helper;

// Настройки подключения к RabbitMQ
$host = getenv('rabbitmq_host');
$port = getenv('rabbitmq_port');
$user = getenv('rabbitmq_user');
$password = getenv('rabbitmq_password');
$queueName = 'service-user';

// URL, на который отправляем POST
$webhookUrl = getenv('host').'/user/rabbitmq-test';

// Оповещения
$helper = new Helper();
$client = new Client();

try {

    $connection = new AMQPStreamConnection($host, $port, $user, $password);
    $channel = $connection->channel();

    // Объявляем очередь (устойчивую, если нужно)
    $channel->queue_declare($queueName, false, true, false, false);

    // Callback-функция при получении сообщения
    $callback = function ($msg) use ($helper, $client, $webhookUrl) {

        $helper->setNotification(0, 'rabbitmq-receive', 'Callback-функция при получении сообщения');

        try {
            $response = $client->post($webhookUrl, [
                'json' => ['message' => $msg->body],
                'timeout' => 1 // Таймаут на случай недоступности сервера
            ]);

            // Подтверждаем только после успешной обработки
            $msg->ack();

            echo " [✓] Отправлено на {$webhookUrl}\n Статус: ", $response->getStatusCode(), "\n";

            $helper->setNotification(0, 'rabbitmq-receive', '[✓] Получено: '.$msg->body);

        } catch (Exception $e) {

            echo " [✗] Ошибка при отправке: ", $e->getMessage(), "\n";

            $helper->setNotification(0, 'rabbitmq-receive', '[✗] Ошибка при отправке: '.$e->getMessage());

            // Отказываемся от сообщения с requeue=true
            $msg->nack(false, true);
        }
    };

    // Подписываемся с no_ack=false (важно!)
    $channel->basic_consume(
        $queueName,
        '',
        false, // no_local
        false, // no_ack - ДОЛЖНО БЫТЬ false для ручного ack/nack
        false, // exclusive
        false, // nowait
        $callback
    );

    echo " [*] Ожидание сообщений. Для выхода нажмите Ctrl+C\n";

    // Основной цикл обработки
    while ($channel->is_open()) {

        try {

            $channel->wait();

        } catch (Exception $e) {

            echo " [✗] Ошибка в основном цикле: ", $e->getMessage(), "\n";
            // Можно добавить задержку перед повторной попыткой
            sleep(5);
        }
    }

} catch (Exception $e) {

    echo " [✗] Критическая ошибка: ", $e->getMessage(), "\n";

    $helper->setNotification(1, 'rabbitmq-receive', 'Критическая ошибка: '.$e->getMessage());

    // Попытка корректно закрыть соединение при ошибке
    if (isset($channel)) {
        $channel->close();
    }
    if (isset($connection)) {
        $connection->close();
    }

    // Выход с кодом ошибки для перезапуска через supervisord
    exit(1);
}