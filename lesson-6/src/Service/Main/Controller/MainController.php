<?php
namespace App\Service\Main\Controller;

class MainController
{
    public function get(): void
    {
        try {

            http_response_code(200);

            echo '<h1>arch.homework</h1>';
            if (!isset($_COOKIE['user_jwt'])) {

                echo '<p><a href="/user/login">Авторизация</a> | <a href="/user/register">Регистрация</a></p>';

            } else {

                echo '<p><a href="/user/profile">Профиль</a></p>';
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