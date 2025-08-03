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

    public function token()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if ($data === null or !isset($data['user_id']) or empty($data['user_id'])) {

            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }

        try {

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

            http_response_code(201);
            echo json_encode($jwt_token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    public function validate()
    {
        try {

            try {

                $jwt_token = $_COOKIE['user_jwt'] ?? $_SERVER['HTTP_COOKIE']['user_jwt'] ?? '';

                if ($jwt_token == '') {

                    http_response_code(401);
                    echo json_encode(['error' => 'Invalid Token'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                    exit;
                }

                # Секретный код
                $jwt_secret = getenv('jwt_secret');

                $signer = new HS256(new HmacKey($jwt_secret));

                $validator = new DefaultValidator();
                $validator->addOptionalRule('exp', new NewerThan(time()), false);

                $parser = new Parser($signer, $validator);

                $claims = $parser->parse($jwt_token);

                # Получаем данные токена
                $jwt_token_data = $this->auth->validate($claims['user_id'], $jwt_token);

                if (isset($jwt_token_data['jwt_token_id']) and !empty($jwt_token_data['jwt_token_id'])) {

                    http_response_code(200);
                    return;

                } else {

                    http_response_code(401);
                    echo json_encode(['error' => 'Invalid Token'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                    exit;
                }

            } catch (ValidationException $e) {

                http_response_code(401);
                echo json_encode(['error' => 'Invalid Token'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                exit;
            }

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    # Получение данных JWT-токена
    public function data()
    {
        try {

            try {

                $jwt_token = $_COOKIE['user_jwt'] ?? $_SERVER['HTTP_COOKIE']['user_jwt'] ?? '';

                if ($jwt_token == '') {

                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
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

                        http_response_code(401);
                        echo json_encode(['error' => 'Invalid Token'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                        return;
                    }

                    http_response_code(200);
                    echo json_encode($jwt_token_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                    return;

                } else {

                    http_response_code(401);
                    echo json_encode(['error' => 'Invalid Token'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                    return;
                }

            } catch (ValidationException $e) {

                http_response_code(401);
                echo json_encode(['error' => 'Invalid Token'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                return;
            }

        } catch (\Exception $e) {

            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }

    # Выход
    public function exit()
    {
        try {

            $jwt_token = $_COOKIE['user_jwt'] ?? $_SERVER['HTTP_COOKIE']['user_jwt'] ?? '';

            if ($jwt_token == '') {

                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
                return;
            }

            # Получаем данные токена
            $jwt_token_data = $this->auth->data($jwt_token);

            if (isset($jwt_token_data['jwt_token_id']) and !empty($jwt_token_data['jwt_token_id'])) {

                # Деактивация текущих JWT-токенов пользователя
                $this->auth->deactivate($jwt_token_data['user_id']);

                http_response_code(200);
                return;

            } else {

                return;
            }

        } catch (\Throwable $e) {

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;

        } catch (\Exception $e) {

            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
            return;
        }
    }
}