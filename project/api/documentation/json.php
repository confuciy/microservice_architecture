<?php
#docker exec -it scripts-php-fpm-1 ./vendor/bin/openapi /var/www/newsite.ru/htdocs/api/controllers/v1 -l -o /var/www/newsite.ru/htdocs/api/controllers/v1/documentation/api.json -f json --version 3.1.0

//error_reporting(E_ALL);
//ini_set('display_errors', true);

spl_autoload_register(function ($name) {

    $config = require __DIR__ . '/../../../config/Config.php';

    $not_autoloader_name_arr = $config['not_autoloader_name_arr'];

    if (in_array($name, $not_autoloader_name_arr)) {

        return;
    }

//    echo $name.'<br>';

    if ($name == 'Api') {

        if (file_exists(__DIR__ .'/../index.php')) {

            # При отработке Swagger'а не стартуем API
            $_GET['not_start'] = 1;

            require_once __DIR__ .'/../index.php';
        }
    }

    if (file_exists(__DIR__ .'/../'.$name.'.php')) {

        require_once __DIR__ .'/../'.$name.'.php';
    }
});

require __DIR__ . '/../vendor/autoload.php';

$openapi_class = new \OpenApi\Generator();
$openapi_class->setVersion('3.1.0');
$openapi = $openapi_class->scan(['/var/www/html/src']);
//var_dump($openapi);
//die;


header('Content-Type: application/json');
echo $openapi->toJson();