<?php
namespace App\Helper;

class Helper
{
    # Получение данных JWT-токина пользователя
    public function getJWTtokenData(): array
    {
        // Формируем строку с кукой
        $cookie_string = 'user_jwt=' . urlencode($_COOKIE['user_jwt']);

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/auth/data');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json-patch+json',
            'Cookie: ' . $cookie_string  // Передаем куку в заголовке
        ]);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);  // Альтернативный способ передачи куки
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
    public function setNotification(int $userId, string $action, string $message): array
    {
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, getenv('host').'/notification');
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        #curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['login' => $this->login, 'passwordHash' => hash('sha512', $this->password)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $userId, 'action' => $action, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
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

            http_response_code(401);
            echo json_encode(['error' => 'Invalid /notification data!']);
        }

        return json_decode($content, true);
    }
}