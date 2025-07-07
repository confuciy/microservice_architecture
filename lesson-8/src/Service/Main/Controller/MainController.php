<?php
namespace App\Service\Main\Controller;

use App\Helper\Helper;

class MainController
{
    private $helper;

    public function __construct()
    {
        $this->helper = new Helper();
    }

    public function get()
    {
        try {

            http_response_code(200);

            $this->helper->getHeader();

            echo '<body>';

            echo '<style>body, div, p {margin: 0}</style>';
            echo '<div style="width: 100%; background: lightgrey;">
                <div style="padding: 10px;">
                    <h1>arch.homework</h1>
                </div>
            </div>';

            echo '<div style="padding: 10px;">';

                if (!isset($_COOKIE['user_jwt'])) {

                    echo '<p><a href="/user/login">Авторизация</a> | <a href="/user/register">Регистрация</a></p>';

                } else {

                    echo '<p><a href="/user/profile">Профиль</a></p><br>';
                    echo '<p><a href="/user/edit">Редактирование профиля пользователя</a></p><br>';
                    echo '<p><a href="/user/order">Заказы</a></p><br>';
                    echo '<p><a href="/user/billing">Пополнение биллинг-аккаунта</a></p><br>';
                    echo '<p><a href="/user/notification">Уведомления</a></p><br>';
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

            echo '</div>';

            echo '</body>';

            return;

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }
}