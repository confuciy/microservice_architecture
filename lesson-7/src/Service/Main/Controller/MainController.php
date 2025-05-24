<?php
namespace App\Service\Main\Controller;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
#use GuzzleHttp\Client;
use App\Helper\Helper;

class MainController
{
    private $helper;

    public function __construct()
    {
        $this->helper = new Helper();
    }

    public function rabbitmqSend(): void
    {
        # Добавляем оповещение
        $this->helper->setNotification(0, 'rabbitmq-send', 'Пытаемся отправить сообщение');

        // Настройки подключения к RabbitMQ
        $host = getenv('rabbitmq_host');
        $port = getenv('rabbitmq_port');
        $user = getenv('rabbitmq_user');
        $password = getenv('rabbitmq_password');
        $queueName = 'service-user';

        try {

            // Создаем соединение
            $connection = new AMQPStreamConnection($host, $port, $user, $password);
            $channel = $connection->channel();

            // Объявляем очередь (если её нет, она будет создана)
            $channel->queue_declare($queueName, false, true, false, false);

            // Текст сообщения
            $messageText = 'Привет, RabbitMQ!';

            // Создаем сообщение
            $message = new AMQPMessage($messageText);

            // Отправляем сообщение в очередь
            $channel->basic_publish($message, '', $queueName);

            echo "Сообщение отправлено: '$messageText'\n";

            # Добавляем оповещение
            $this->helper->setNotification(0, 'rabbitmq-send', 'Сообщение отправлено: '.$messageText);

            // Закрываем соединение
            $channel->close();
            $connection->close();

        } catch (Exception $e) {

            echo "Ошибка: " . $e->getMessage() . "\n";
        }
    }

    public function get(): void
    {
        try {

            http_response_code(200);

            echo '<h1>arch.homework</h1>';
            if (!isset($_COOKIE['user_jwt'])) {

                echo '<p><a href="/user/login">Авторизация</a> | <a href="/user/register">Регистрация</a></p>';

            } else {

                echo '<p><a href="/user/profile">Профиль</a></p>';
                echo '<p><a href="/user/edit">Редактирование профиля пользователя</a></p>';
                echo '<p><a href="/user/exit">Выход</a></p>';
            }

            if (isset($_GET['data'])) {

                echo '<br><br><br>';

                echo '<pre>$_COOKIE: '; print_r($_COOKIE); echo '</pre>';
                echo '<pre>HEADERS: '; print_r(apache_request_headers()); echo '</pre>';
                echo '<pre>$_GET:' ; print_r($_GET); echo '</pre>';
                echo '<pre>$_POST: '; print_r($_POST); echo '</pre>';
                echo '<pre>$_SERVER: '; print_r($_SERVER); echo '</pre>';
            }


            return;

        } catch (\Exception $e) {

            http_response_code(404);

            echo json_encode(['error' => $e->getMessage()]);

            return;
        }
    }
}