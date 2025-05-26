<?php
namespace App\Service\Auth\Controller;

use App\Service\Auth\Model\Auth;
use App\Helper\Helper;
use MiladRahimi\Jwt\Generator;
use MiladRahimi\Jwt\Parser;
use MiladRahimi\Jwt\Cryptography\Keys\HmacKey;
use MiladRahimi\Jwt\Cryptography\Algorithms\Hmac\HS256;
use MiladRahimi\Jwt\Exceptions\ValidationException;
use MiladRahimi\Jwt\Validator\Rules\EqualsTo;
use MiladRahimi\Jwt\Validator\Rules\NewerThan;
use MiladRahimi\Jwt\Validator\DefaultValidator;

class AuthController
{
    private $auth;
    private $helper;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->helper = new Helper();
    }

    public function token(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null or !isset($data['user_id']) or empty($data['user_id'])) {

            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        try {

            # Добавляем оповещение
            $this->helper->setNotification($data['user_id'], 'set_jwt_token', 'Установка JWT-токена для пользователя '.$data['user_id']);

            # Секретный код
            $jwt_secret = getenv('jwt_secret');

            # Дата завершения токена
            $exp = (time() + (3600 * 12));

            // Use HS256 to generate and parse JWTs
            $key = new HmacKey($jwt_secret);
            $signer = new HS256($key);

            // Generate a JWT
            $generator = new Generator($signer);
            $jwt = $generator->generate(['user_id' => $data['user_id'], 'exp' => $exp]);

            # Сохраняем данные JWT-токена в БД
            $jwt_token_id = $this->auth->create($data['user_id'], $jwt, $exp);

            $jwt_token = [
                'jwt_token_id' => $jwt_token_id,
                'jwt' => $jwt,
                'exp' => $exp,
                'user_id' => $data['user_id']
            ];

            # Добавляем оповещение
            $this->helper->setNotification($data['user_id'], 'set_jwt_token_ok', 'Установка JWT-токена для пользователя '.$data['user_id'].' прошла успешно');

            http_response_code(201);
            echo json_encode($jwt_token);
            return;

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            return;

        } catch (\Exception $e) {

            # Добавляем оповещение
            $this->helper->setNotification($data['user_id'], 'set_jwt_token_error', 'Установка JWT-токена для пользователя '.$data['user_id'].' не прошла');

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    public function validate(): void
    {
        try {

            try {

                $jwt_token = $_COOKIE['user_jwt'] ?? $_SERVER['HTTP_COOKIE']['user_jwt'] ?? '';

                if ($jwt_token == '') {

                    # Добавляем оповещение
                    $this->helper->setNotification(0, 'validate_jwt_token_error', 'Не передан JWT-токен');

                    http_response_code(401);
                    echo json_encode(['error' => 'Invalid Token']);
                    exit;
                }

                if ($jwt_token) {

                    //
                }

                # Секретный код
                $jwt_secret = getenv('jwt_secret');

                $signer = new HS256(new HmacKey($jwt_secret));

                $validator = new DefaultValidator();
                $validator->addOptionalRule('exp', new NewerThan(time()), false);

                $parser = new Parser($signer, $validator);

                $claims = $parser->parse($jwt_token);
                #print_r($claims); // ['id' => 13, 'is-admin' => true]

                # Добавляем оповещение
                $this->helper->setNotification($claims['user_id'], 'validate_jwt_token', 'Началась валидация JWT-токена '.$jwt_token.' для пользователя '.$claims['user_id']);

                # Получаем данные токена
                $jwt_token_data = $this->auth->validate($claims['user_id'], $jwt_token);

                if (isset($jwt_token_data['jwt_token_id']) and !empty($jwt_token_data['jwt_token_id'])) {

                    # Добавляем оповещение
                    $this->helper->setNotification($claims['user_id'], 'validate_jwt_token_ok', 'Валидация JWT-токена '.$jwt_token.' для пользователя '.$claims['user_id'].' прошла успешно');

                    http_response_code(200);
                    return;

                } else {

                    # Добавляем оповещение
                    $this->helper->setNotification(0, 'validate_jwt_token_error', 'Валидация JWT-токена '.$jwt_token.' не прошла');

                    http_response_code(401);
                    echo json_encode(['error' => 'Invalid Token']);
                    exit;
                }

            } catch (ValidationException $e) {

                # Добавляем оповещение
                $this->helper->setNotification(0, 'validate_jwt_token_exp_error', 'Валидация JWT-токена '.$jwt_token.' не прошла, JWT-токен просрочен');

                http_response_code(401);
                echo json_encode(['error' => 'Invalid Token']);
                exit;
            }

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

    # Получение данных JWT-токена
    public function data(): void
    {
        try {

            try {

                $jwt_token = $_COOKIE['user_jwt'] ?? $_SERVER['HTTP_COOKIE']['user_jwt'] ?? '';

                if ($jwt_token == '') {

                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON data']);
                    return;
                }

                # Секретный код
                $jwt_secret = getenv('jwt_secret');

                $signer = new HS256(new HmacKey($jwt_secret));

                $validator = new DefaultValidator();
                $validator->addOptionalRule('exp', new NewerThan(time()), false);

                $parser = new Parser($signer, $validator);

                $claims = $parser->parse($jwt_token);

                # Получаем данные токена
                $jwt_token_data = $this->auth->data($jwt_token);

                if (isset($jwt_token_data['jwt_token_id']) and !empty($jwt_token_data['jwt_token_id'])) {

                    if ($jwt_token_data['user_id'] != $claims['user_id']) {

                        # Добавляем оповещение
                        $this->helper->setNotification($claims['user_id'], 'data_jwt_token_error', 'Запрос чужого JWT-токена. Данные JWT-токена '.$jwt_token.' не получены.');

                        ttp_response_code(401);
                        echo json_encode(['error' => 'Invalid Token']);
                        return;
                    }

                    # Добавляем оповещение
                    $this->helper->setNotification($claims['user_id'], 'data_jwt_token_ok', 'Данные JWT-токена '.$jwt_token.' получены успешно');

                    http_response_code(200);
                    echo json_encode($jwt_token_data);
                    return;

                } else {

                    # Добавляем оповещение
                    $this->helper->setNotification($claims['user_id'], 'data_jwt_token_error', 'Данные JWT-токена '.$jwt_token.' не получены');

                    ttp_response_code(401);
                    echo json_encode(['error' => 'Invalid Token']);
                    return;
                }

            } catch (ValidationException $e) {

                http_response_code(401);
                echo json_encode(['error' => 'Invalid Token']);
                return;
            }

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    # Выход
    public function exit(): void
    {
        try {

            $jwt_token = $_COOKIE['user_jwt'] ?? $_SERVER['HTTP_COOKIE']['user_jwt'] ?? '';

            if ($jwt_token == '') {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data']);
                return;
            }

            # Получаем данные токена
            $jwt_token_data = $this->auth->data($jwt_token);

            if (isset($jwt_token_data['jwt_token_id']) and !empty($jwt_token_data['jwt_token_id'])) {

                # Деактивация текущих JWT-токенов пользователя
                $this->auth->deactivate($jwt_token_data['user_id']);

                # Добавляем оповещение
                $this->helper->setNotification($jwt_token_data['user_id'], 'exit_auth_ok', 'Выход для пользователя '.$jwt_token_data['user_id'].' по JWT-токену '.$jwt_token_data['jwt_token'].' успешен');

                http_response_code(200);
                return;

            } else {

                # Добавляем оповещение
                $this->helper->setNotification(0, 'exit_auth_token_error', 'Выход по JWT-токену '.$jwt_token.' не прошел');

                return;
            }

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
}