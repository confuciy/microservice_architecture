<?php

//spl_autoload_register(function ($name) {
//
//    $config = require_once __DIR__ . '/../../../config/Config.php';
//
//    $not_autoloader_name_arr = $config['not_autoloader_name_arr'];
//
//    if (in_array($name, $not_autoloader_name_arr)) {
//
//        return;
//    }
//
////    echo $name.'<br>';
//
//    if ($name == 'Api') {
//
//        if (file_exists(__DIR__ .'/../index.php')) {
//
//            # При отработке Swagger'а не стартуем API
//            $_GET['not_start'] = 1;
//
//            require_once __DIR__ .'/../index.php';
//        }
//    }
//
//    if (file_exists(__DIR__ .'/../'.$name.'.php')) {
//
//        require_once __DIR__ .'/../'.$name.'.php';
//    }
//});

require '../../../../vendor/autoload.php';

$openapi = \OpenApi\Generator::scan([$_SERVER['DOCUMENT_ROOT'] . '/api/controllers/v1']);

header('Content-Type: application/yaml');
echo $openapi->toYaml();