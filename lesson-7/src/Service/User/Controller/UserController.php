<?php
namespace App\Service\User\Controller;

use App\Service\User\Model\User;
use App\Helper\Helper;

class UserController
{
    private $user;
    private $helper;

    public function __construct()
    {
        $this->user = new User();
        $this->helper = new Helper();
    }

    public function rabbitmqTest(): void
    {
        # Добавляем оповещение
        $this->helper->setNotification(0, 'get_rabbitmq_receive', 'Получили ответ из RabbitMQ');

        ob_start();
        echo '<pre>$_POST '; print_r($_POST); echo '</pre>';
        echo '<pre>$_GET '; print_r($_GET); echo '</pre>';
        echo '<pre>$_COOKIE '; print_r($_COOKIE); echo '</pre>';
        $content = ob_get_contents();
        ob_clean();

        # Добавляем оповещение
        $this->helper->setNotification(0, 'get_rabbitmq_receive', 'Текст ответа: '.$content);
    }

    # Редактирование профиля пользователя
    public function edit(): void
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login']);
                return;
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            echo '<h1>arch.homework / Редактирование профиля пользователя</h1>';

            echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';

            echo '<form method="post" action="/user/edit">';
                echo '<table cellpadding="5" cellspacing="1" border="1">';
                echo '<tr>';
                    echo '<td><b>Id</b></td>';
                    echo '<td>'.$user['user_id'].'</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Last Name</b></td>';
                    echo '<td><input type="text" name="last_name" value="'.htmlentities($user['last_name'], ENT_QUOTES, 'UTF-8').'"></td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>First Name</b></td>';
                    echo '<td><input type="text" name="first_name" value="'.htmlentities($user['first_name'], ENT_QUOTES, 'UTF-8').'"></td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Email</b></td>';
                    echo '<td>'.$user['email'].'</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Phone</b></td>';
                    echo '<td><input type="text" name="phone" value="'.htmlentities($user['phone'], ENT_QUOTES, 'UTF-8').'"></td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Address</b></td>';
                    echo '<td><input type="text" name="address" value="'.htmlentities($user['address'], ENT_QUOTES, 'UTF-8').'" size="40"></td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td colspan="2"><input type="submit" value="Сохранить"></td>';
                echo '</tr>';
                echo '</table>';
            echo '</form>';

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Профиль пользователя
    public function profile(): void
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login']);
                return;
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            echo '<h1>arch.homework / Профиль пользователя</h1>';

            echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';

            echo '<table cellpadding="5" cellspacing="1" border="1">';
                echo '<tr>';
                    echo '<td><b>Id</b></td>';
                    echo '<td>'.$user['user_id'].'</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Last Name</b></td>';
                    echo '<td>'.$user['last_name'].'</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>First Name</b></td>';
                    echo '<td>'.$user['first_name'].'</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Email</b></td>';
                    echo '<td>'.$user['email'].'</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Phone</b></td>';
                    echo '<td>'.$user['phone'].'</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Address</b></td>';
                    echo '<td>'.$user['address'].'</td>';
                echo '</tr>';
            echo '</table>';

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Страница с регистрацией
    public function register(): void
    {
        if (isset($_COOKIE['user_jwt']) and !empty($_COOKIE['user_jwt'])) {

            http_response_code(401);
            echo json_encode(['error' => 'You a not login']);
            return;
        }

        echo '<h1>arch.homework / Регистрация пользователя</h1>';

        echo '<p><a href="/">Главная</a></p>';

        echo '<form method="post" action="/user/create">';
            echo 'Email:<br><input name="email" type="text"><br>';
            echo 'Password:<br><input name="password" type="password"><br>';
            echo '<br><br>';
            echo 'Last Name:<br><input name="last_name" type="text"><br>';
            echo 'First Name:<br><input name="first_name" type="text"><br>';
            echo 'Phone:<br><input name="phone" type="text"><br>';
            echo 'Address:<br><input name="address" type="text" size="40"><br>';
            echo '<br>';
            echo '<input type="submit" value="Отправить">';
        echo '</form>';

        return;
    }

    # Страница с логином
    public function login(): void
    {
        if (isset($_COOKIE['user_jwt']) and !empty($_COOKIE['user_jwt'])) {

            http_response_code(401);
            echo json_encode(['error' => 'You a not login']);
            return;
        }

        echo '<h1>arch.homework / Авторизация пользователя</h1>';

        echo '<p><a href="/">Главная</a></p>';

        echo '<form method="post" action="/user/auth">';
            echo 'Email:<br><input name="email" type="text"><br>';
            echo 'Password:<br><input name="password" type="password"><br>';
            echo '<br>';
            echo '<input type="submit" value="Отправить">';
        echo '</form>';

        return;
    }

    # Авторизация пользователя, установка COOKIE
    public function auth(): void
    {
        if (
            !isset($_POST['email']) or trim($_POST['email']) == ''
            or !isset($_POST['password']) or trim($_POST['password']) == ''
        ) {

            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password!']);
            return;
        }

        try {

            $email = trim($_POST['email']);
            $password = trim($_POST['password']);

            $user = $this->user->getUserByEmailAndPassword($email, '*'.strtoupper(sha1(sha1($password, true))));

            if (!isset($user['user_id']) or empty($user['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'Login error! Check your email or password']);
            }

            # Создание JWT-токен пользователя
            $data = $this->user->createJWTtoken($user['user_id']);

            # Устанавливаем COOKIE
            setcookie('user_jwt', $data['jwt'], [
                'expires' => $data['exp'],
                'path' => '/',
                'domain' => getenv('domain'),
                'secure' => false,    // Для HTTPS
                'httponly' => true,  // Защита от XSS
                'samesite' => 'Lax'
            ]);

            $headers = apache_request_headers();

            if (isset($headers['Postman-Token'])) {

                echo json_encode($data);

            } else {

                header('Location: /user/profile');
            }

            return;

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Выход пользователя
    public function exit(): void
    {
        try {

            # Выполняем выход пользователя
            $this->user->exit();

            # Удаление COOKIE
            setcookie('user_jwt', '', -1, '/', getenv('domain'));
            unset($_COOKIE['user_jwt']);

            $headers = apache_request_headers();

            if (isset($headers['Postman-Token'])) {

                echo json_encode(['message' => 'The exit was successful']);

            } else {

                header('Location: /');
            }

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Создание пользователя
    public function create(): void
    {
        $headers = apache_request_headers();

        # Добавляем оповещение
        $this->helper->setNotification(0, 'create_user_function', 'Зашли в метод создания пользователя');

        if (isset($headers['Postman-Token'])) {

            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null) {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }

        } else {

            if (!sizeof($_POST)) {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }

            # Данные пользователя
            $data = $_POST;
        }

        try {

            if (
                !isset($data['email']) or $data['email'] == ''
                or !isset($data['password']) or $data['password'] == ''
            ) {
                # Добавляем оповещение
                $this->helper->setNotification(0, 'create_user_error', 'Нет данных о почте или пароле');

                echo json_encode(['error' => 'Empty Email or Password']);
                return;
            }

            if ($this->user->checkUserExists($data['email']) == true) {

                # Добавляем оповещение
                $this->helper->setNotification(0, 'create_user_error', 'Пользователь с почтой '.$data['email'].' уже существует');

                echo json_encode(['error' => 'User with Email '.$data['email'].' has yet exists']);
                return;
            }

            # Заполняем username
            $data['username'] = explode('@', $data['email'])[0];

            # Создаем пользователя
            $user = $this->user->create($data);

            # Добавляем оповещение
            $this->helper->setNotification($user['user_id'], 'create_user_ок', 'Пользователь с почтой '.$data['email'].' создан с id '.$user['user_id']);

            if (isset($headers['Postman-Token'])) {

                http_response_code(201);
                echo json_encode($user);

            } else {

                header('Location: /user/login');
            }

            return;

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Получение пользователя - для Postman'а
    public function get(int $userId): void
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login']);
                return;
            }
            if ($jwt_token_data['user_id'] != $userId) {

                http_response_code(401);
                echo json_encode(['error' => 'It is not your profile']);
                return;
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            $headers = apache_request_headers();

            if (isset($headers['Postman-Token'])) {

                echo json_encode($user);

            } else {

                echo '<h1>arch.homework / Профиль пользователя</h1>';

                echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';

                echo '<table cellpadding="5" cellspacing="1" border="1">';
                echo '<tr>';
                    echo '<td><b>Id</b></td>';
                    echo '<td>' . $user['user_id'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Last Name</b></td>';
                    echo '<td>' . $user['last_name'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>First Name</b></td>';
                    echo '<td>' . $user['first_name'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Email</b></td>';
                    echo '<td>' . $user['email'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Phone</b></td>';
                    echo '<td>' . $user['phone'] . '</td>';
                echo '</tr>';
                echo '<tr>';
                    echo '<td><b>Address</b></td>';
                    echo '<td>'.$user['address'].'</td>';
                echo '</tr>';
                echo '</table>';
            }

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Обновление пользователя
    public function update(int $userId): void
    {
        $headers = apache_request_headers();

        if (isset($headers['Postman-Token'])) {

            $data = json_decode(file_get_contents('php://input'), true);

            if ($data === null) {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }

        } else {

            if (!sizeof($_POST)) {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }

            # Данные пользователя
            $data = $_POST;
        }

        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login']);
                return;
            }
            if ($jwt_token_data['user_id'] != $userId) {

                http_response_code(401);
                echo json_encode(['error' => 'It is not your profile']);
                return;
            }

            # Обновляем пользователя
            $user = $this->user->update($userId, $data);

            # Добавляем оповещение
            $this->helper->setNotification($userId, 'update_user_ок', 'Пользователь успешно обновлен');

            if (isset($headers['Postman-Token'])) {

                http_response_code(204);
                echo json_encode($user);

            } else {

                header('Location: /user/profile');
            }

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Удаление пользователя
    public function delete(int $userId): void
    {
        try {

            $user = $this->user->delete($userId);

            echo json_encode($user);

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }
}