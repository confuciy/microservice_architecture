<?php
namespace App\Service\User\Controller;

use App\Service\User\Model\User;
use App\Helper\Helper;

/**
 * @OA\Tags(
 *     name="User | Сервис пользователей"
 * )
 */
class UserController
{
    private $user;
    private $helper;

    public function __construct()
    {
        $this->user = new User();
        $this->helper = new Helper();
    }

    # Страница редактирования профиля пользователя
    public function profileEdit()
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login']);
                return;
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            echo '<style>body, div, p {margin: 0}</style>';
            echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Редактирование профиля пользователя</h1>
                </div>
            </div>';

            echo '<div style="padding: 10px;">';

                echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';
                echo '<br><br>';

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

            echo '</div>';

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Страница с профилем пользователя
    public function profile()
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                echo json_encode(['error' => 'You a not login']);
                return;
            }

            $user = $this->user->get($jwt_token_data['user_id']);

            echo '<style>body, div, p {margin: 0}</style>';
            echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Профиль пользователя</h1>
                </div>
            </div>';

            echo '<div style="padding: 10px;">';

                echo '<p><a href="/">Главная</a> | <a href="/user/exit">Выход</a></p>';
                echo '<br><br>';

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

            echo '</div>';

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Страница с регистрацией
    public function register()
    {
        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Регистрация пользователя</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            echo '<form method="post" action="/user/create">';
                echo '<input type="hidden" name="reload" value="1">';
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

        echo '</div>';

        return;
    }

    # Страница с логином
    public function login()
    {
        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Авторизация пользователя</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            echo '<form method="post" action="/user/auth">';
                echo '<input type="hidden" name="reload" value="1">';
                echo 'Email:<br><input name="email" type="text"><br>';
                echo 'Password:<br><input name="password" type="password"><br>';
                echo '<br>';
                echo '<input type="submit" value="Отправить">';
            echo '</form>';

        echo '</div>';

        return;
    }

    # Страница c уведомлениями пользователя
    public function notification()
    {
        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Уведомления пользователя</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            # Получаем уведомления пользователя
            $notification_list = $this->user->getNotificationList();

            if (isset($notification_list['notification_list']) and sizeof($notification_list['notification_list']) > 0) {

                $col = 1;

                echo '<table cellpadding="5" cellspacing="1" border="1">';

                    echo '<tr>';
                        echo '<td style="text-align: center; font-weight: bold;">#</td>';
                        echo '<td style="text-align: center; font-weight: bold;">Действие</td>';
                        echo '<td style="text-align: center; font-weight: bold;">Сообщение</td>';
                        echo '<td style="text-align: center; font-weight: bold;">Дата добавления</td>';
                    echo '</tr>';

                    foreach ($notification_list['notification_list'] as $notification) {

                        echo '<tr>';
                            echo '<td>'.$col.'</td>';
                            echo '<td>'.$notification['action'].'</td>';
                            echo '<td>'.$notification['message'].'</td>';
                            echo '<td>'.date('d.m.Y H:i:s', strtotime(substr($notification['date_insert'], 0, 19))).'</td>';
                        echo '</tr>';

                        $col++;
                    }

                echo '</table>';
            }

        echo '</div>';

        return;
    }

    # Страница c заказами
    public function order()
    {
        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Заказы пользователя</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

        echo '<p><a href="/">Главная</a></p>';
        echo '<br><br>';

        # Получаем заказы пользователя
        $order_list = $this->user->getOrderList();

        if (isset($order_list['order_list']) and sizeof($order_list['order_list']) > 0) {

            $col = 1;

            $order_status = [
                0 => "новый",
                1 => "ожидает оплаты",
                2 => "оплаче",
                3 => "ожидает доставки",
                5 => "доставлен",
                6 => "отменен"
            ];

            echo '<table cellpadding="5" cellspacing="1" border="1">';

            echo '<tr>';
                echo '<td style="text-align: center; font-weight: bold;">#</td>';
                echo '<td style="text-align: center; font-weight: bold;">Сумма</td>';
                echo '<td style="text-align: center; font-weight: bold;">Статус</td>';
                echo '<td style="text-align: center; font-weight: bold;">Дата добавления</td>';
            echo '</tr>';

            foreach ($order_list['order_list'] as $order) {

                echo '<tr>';
                    echo '<td>'.$col.'</td>';
                    echo '<td>'.$order['amount'].'</td>';
                    echo '<td>'.$order_status[$order['status']].'</td>';
                    echo '<td>'.date('d.m.Y H:i:s', strtotime(substr($order['date_insert'], 0, 19))).'</td>';
                echo '</tr>';

                $col++;
            }

            echo '</table>';
        }

        echo '</div>';

        return;
    }

    # Страница биллинг-аккаунта
    public function billing()
    {
        echo '<style>body, div, p {margin: 0}</style>';
        echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework / Пополнение биллинг-аккаунта</h1>
                </div>
            </div>';

        echo '<div style="padding: 10px;">';

            echo '<p><a href="/">Главная</a></p>';
            echo '<br><br>';

            # Получаем биллинг-аккаунт
            $billing = $this->user->getBilling();

            if (sizeof($billing) > 0) {

                echo '<div style="padding: 5px; background: lightgrey;">
                    Текущая сумма биллинг-аккаунта: <span style="font-size: 14px; font-weight: bold;">'.$billing['amount'].'</span>
                </div>';
                echo '<br><br>';
            }

            echo '<form method="post" action="/billing/amount">';
                echo '<input type="hidden" name="reload" value="1">';
                echo '<input type="hidden" name="action" value="plus">';
                echo 'Сумма пополнения:<br><input name="amount" type="text"><br>';
                echo '<br>';
                echo '<input type="submit" value="Пополнить">';
            echo '</form>';

        echo '</div>';

        return;
    }

    /**
     * @OA\Post(
     *     path="/user/auth",
     *     summary="Авторизация пользователя",
     *     description="",
     *     tags={"User | Сервис пользователей"},
     *     operationId="user_auth",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"email", "password"},
     *                 @OA\Property(description="Email пользователя", property="email", type="string", format="string"),
     *                 @OA\Property(description="Пароль", property="password", type="string", format="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/UserAuthResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="UserAuthResponse",
     *     title="Авторизация пользователя",
     *     description="",
     *     @OA\Property(property="jwt_token_id", type="integer", example="1"),
     *     @OA\Property(property="jwt", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2V..."),
     *     @OA\Property(property="exp", type="integer", example="1746674949"),
     *     @OA\Property(property="user_id", type="integer", example="1")
     * )
     *
     * @throws \Exception
     */
    public function auth()
    {
        try {

            if (
                !isset($_POST['email']) or trim($_POST['email']) == ''
                or !isset($_POST['password']) or trim($_POST['password']) == ''
            ) {
                http_response_code(401);
                throw new \Exception('Проверьте свой Email или пароль');
            }

            $email = trim($_POST['email']);
            $password = trim($_POST['password']);

            $user = $this->user->getUserByEmailAndPassword($email, '*'.strtoupper(sha1(sha1($password, true))));

            if (!isset($user['user_id']) or empty($user['user_id'])) {

                http_response_code(401);
                throw new \Exception('Авторизация провалилась. Проверьте свой Email или пароль');
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

            if (isset($_POST['reload'])) {

                header('Location: /user/profile');

            } else {

                echo json_encode($data);
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

    /**
     * @OA\Get(
     *     path="/user/exit",
     *     summary="Выход пользователя",
     *     description="",
     *     tags={"User | Сервис пользователей"},
     *     security={{"cookieAuth": {}}},
     *     operationId="user_exit",
     *     deprecated=false,
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @throws \Exception
     */
    public function exit()
    {
        try {

            # Выполняем выход пользователя
            $this->user->exit();

            # Удаление COOKIE
            setcookie('user_jwt', '', -1, '/', getenv('domain'));
            unset($_COOKIE['user_jwt']);

            $headers = apache_request_headers();

            if (isset($headers['Postman-Token'])) {

                echo json_encode(['message' => 'Выход произведен успешно']);

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

    /**
     * @OA\Post(
     *     path="/user/create",
     *     summary="Регистрация пользователя",
     *     description="",
     *     tags={"User | Сервис пользователей"},
     *     security={{"cookieAuth": {}}},
     *     operationId="user_create",
     *     deprecated=false,
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"email", "password"},
     *                 @OA\Property(description="Email пользователя", property="email", type="string", format="string"),
     *                 @OA\Property(description="Пароль", property="password", type="string", format="string"),
     *                 @OA\Property(description="Имя", property="first_name", type="string", format="string"),
     *                 @OA\Property(description="Фамилия", property="last_name", type="string", format="string"),
     *                 @OA\Property(description="Телефон", property="phone", type="string", format="string"),
     *                 @OA\Property(description="Адрес", property="address", type="string", format="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/UserCreateResponse")
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="401 Authorization Required"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad Request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="UserCreateResponse",
     *     title="Пользователь",
     *     description="",
     *     @OA\Property(property="username", type="string", example="gorbachev"),
     *     @OA\Property(property="first_name", type="string", example="Aleksey"),
     *     @OA\Property(property="last_name", type="string", example="Gorbachev"),
     *     @OA\Property(property="email", type="string", example="email@email.com"),
     *     @OA\Property(property="password", type="string", example="12345"),
     *     @OA\Property(property="phone", type="string", example="+71112223344"),
     *     @OA\Property(property="address", type="string", example="Moscow, Red Square, 1")
     * )
     *
     * @throws \Exception
     */
    public function create()
    {
        try {

            $headers = apache_request_headers();

            # Добавляем оповещение
            $this->helper->setNotification(0, 'create_user_function', 'Зашли в метод создания пользователя');

            if (isset($headers['Postman-Token'])) {

                $data = json_decode(file_get_contents('php://input'), true);

                if ($data === null) {
                    throw new \Exception('JSON поврежден');
                }

            } else {

                if (!sizeof($_POST)) {
                    throw new \Exception('JSON поврежден');
                }

                # Данные пользователя
                $data = $_POST;
            }

            if (
                !isset($data['email']) or $data['email'] == ''
                or !isset($data['password']) or $data['password'] == ''
            ) {
                # Добавляем оповещение
                $this->helper->setNotification(0, 'create_user_error', 'Нет данных о почте или пароле');

                throw new \Exception('Пустой Email или пароль');
            }

            if ($this->user->checkUserExists($data['email']) == true) {

                # Добавляем оповещение
                $this->helper->setNotification(0, 'create_user_error', 'Пользователь с почтой '.$data['email'].' уже существует');

                throw new \Exception('Пользователь с Email '.$data['email'].' уже существует');
            }

            # Заполняем username
            $data['username'] = explode('@', $data['email'])[0];

            # Создаем пользователя
            $user = $this->user->create($data);

            # Добавляем оповещение
            $this->helper->setNotification($user['user_id'], 'create_user_ok', 'Пользователь с почтой '.$data['email'].' создан с id '.$user['user_id']);


            # Создаем аккаунт

            # Данные для отправки
            $data = [
                'action' => 'create',
                'data' => [
                    'user_id' => $user['user_id'],
                    'amount' => 0
                ]
            ];

            # Создаем аккаунт в сервисе биллинга
            # Отправляем сообщение в RabbitMQ
            $this->helper->rabbitmqSend('service-billing', json_encode($data));

            if (isset($_POST['reload'])) {

                header('Location: /user/login');

            } else {

                http_response_code(201);
                echo json_encode($user);
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

    /**
     * Получение пользователя - для Postman'а
     * @param int $userId
     * @throws \Exception
     */
    public function get(int $userId)
    {
        try {

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                throw new \Exception('Вы не авторизованы');
            }
            if ($jwt_token_data['user_id'] != $userId) {

                http_response_code(401);
                throw new \Exception('Это не Ваш профиль');
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

    /**
     * Обновление пользователя
     * @param int $userId
     */
    public function update(int $userId)
    {
        try {

            $headers = apache_request_headers();

            if (isset($headers['Postman-Token'])) {

                $data = json_decode(file_get_contents('php://input'), true);

                if ($data === null) {

                    http_response_code(400);
                    throw new \Exception('JSON поврежден');
                }

            } else {

                if (!sizeof($_POST)) {

                    http_response_code(400);
                    throw new \Exception('JSON поврежден');
                }

                # Данные пользователя
                $data = $_POST;
            }

            $jwt_token_data = $this->helper->getJWTtokenData();

            if (!isset($jwt_token_data['user_id']) or empty($jwt_token_data['user_id'])) {

                http_response_code(401);
                throw new \Exception('Вы не авторизованы');
            }
            if ($jwt_token_data['user_id'] != $userId) {

                http_response_code(401);
                throw new \Exception('Это не Ваш профиль');
            }

            # Обновляем пользователя
            $user = $this->user->update($userId, $data);

            # Добавляем оповещение
            $this->helper->setNotification($userId, 'update_user_ok', 'Пользователь успешно обновлен');

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

    /**
     * Удаление пользователя
     * @param int $userId
     */
    public function delete(int $userId)
    {
        try {

            $user = $this->user->delete($userId);

            echo json_encode($user);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }
}