<?php
namespace App\Helper;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Helper
{
    # Получение данных JWT-токина пользователя
    public function getJWTtokenData(): array
    {
        # Формируем строку с кукой
        $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/auth/data');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json-patch+json',
            'Cookie: ' . $cookie_string  # Передаем куку в заголовке
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  # Альтернативный способ передачи куки
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $content = curl_exec($ch);

        if ($content == '') {

            throw new \Exception("Invalid /user/data");
        }

        return json_decode($content, true);
    }

    # Добавление оповещения
    public function setNotification(int $userId = 0, string $action = '', string $message = ''): void
    {
        if ($action == '') {
            throw new \Exception("Invalid action");
        }
        if ($message == '') {
            throw new \Exception("Invalid message");
        }

        # Данные для отправки
        $data = [
            'action' => 'create',
            'data' => [
                'user_id' => $userId,
                'action' => $action,
                'message' => $message
            ]
        ];

        # Создаем аккаунт в сервисе биллинга
        # Отправляем сообщение в RabbitMQ
        $this->rabbitmqSend('service-notification', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));

        return;

//        $ch = curl_init();
//        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
//        curl_setopt($ch, CURLOPT_URL, getenv('host').'/notification');
//        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
//        curl_setopt($ch, CURLOPT_POST, 1);
//        #curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['login' => $this->login, 'passwordHash' => hash('sha512', $this->password)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
//        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $userId, 'action' => $action, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
//        curl_setopt($ch, CURLOPT_NOBODY, 0);
//        curl_setopt($ch, CURLOPT_HEADER, 0);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
//        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
//        $content = curl_exec($ch);
//
//        if ($content == '') {
//
//            http_response_code(401);
//            echo json_encode(['error' => 'Invalid /notification data!']);
//        }
//
//        return json_decode($content, true);
    }

    # Отправка сообщения в RabbitMQ
    public function rabbitmqSend(string $queueName = '', string $messageText = ''): void
    {
        if ($queueName == '') {
            throw new \Exception("Invalid queueName");
        }
        if ($messageText == '') {
            throw new \Exception("Invalid messageText");
        }

        # Добавляем оповещение
        #$this->setNotification(0, 'rabbitmq-send', 'Пытаемся отправить сообщение');

        // Настройки подключения к RabbitMQ
        $host = getenv('rabbitmq_host');
        $port = getenv('rabbitmq_port');
        $user = getenv('rabbitmq_user');
        $password = getenv('rabbitmq_password');

        try {

            // Создаем соединение
            $connection = new AMQPStreamConnection($host, $port, $user, $password);
            $channel = $connection->channel();

            // Объявляем очередь (если её нет, она будет создана)
            $channel->queue_declare($queueName, false, true, false, false);

            // Создаем сообщение
            $message = new AMQPMessage($messageText);

            // Отправляем сообщение в очередь
            $channel->basic_publish($message, '', $queueName);

            # Добавляем оповещение
            #$this->setNotification(0, 'rabbitmq-send', 'Сообщение отправлено: '.$messageText);

            // Закрываем соединение
            $channel->close();
            $connection->close();

        } catch (Exception $e) {

            echo "Ошибка: " . $e->getMessage() . "\n";
        }
    }

    # Отправка запроса в сервис
    public function sendData(array $data = []): array
    {
        if (!isset($data['url']) or $data['url'] == '') {
            throw new \Exception("Invalid url");
        }
        if (!isset($data['method']) or $data['method'] == '') {
            throw new \Exception("Invalid url");
        }
        if (!is_array($data) or !sizeof($data)) {
            throw new \Exception("Invalid data");
        }

        if (isset($data['user_jwt']) and $data['user_jwt'] != '') {
            # Формируем строку с кукой
            $cookie_string = 'user_jwt=' . urlencode($data['user_jwt']);
        }


        # Если метод POST
        if ($data['method'] == 'post') {

            $ch = curl_init();
            $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
            curl_setopt($ch, CURLOPT_URL, $data['url']);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
//        curl_setopt($ch, CURLOPT_HTTPHEADER, [
//            'Content-Type: application/json-patch+json',
//            'Cookie: ' . $cookie_string  # Передаем куку в заголовке
//        ]);
            if (isset($data['user_jwt']) and $data['user_jwt'] != '') {
                curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  # Альтернативный способ передачи куки
            }
            curl_setopt($ch, CURLOPT_NOBODY, 0);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
            #curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json-patch+json']);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            $content = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        if ($content == '') {

            throw new \Exception("Invalid ".$data['url']);
        }

        return json_decode(['content' => $content, 'httpcode' => $httpcode], true);
    }

    public function getHeader()
    {

        echo '
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <title>ARCH.HOMEWORK</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width,initial-scale=1.0">
            <script src="/js/jquery-1.9.1.min.js"></script>
        </head>';
    }
}