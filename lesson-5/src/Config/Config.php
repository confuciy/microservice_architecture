<?php
namespace App\Config;

class Config
{
    public static function loadConfig(): array
    {
        return [
//            'host' => getenv('db_host'),
//            'port' => getenv('db_port'),
//            'dbname' => getenv('db_name'),
//            'user' => getenv('db_user'),
//            'password' => base64_decode(getenv('db_password')),
//            'schema' => getenv('db_schema'),
            'host' => getenv('POSTGRE_HOST'),
            'port' => getenv('POSTGRE_PORT'),
            'dbname' => getenv('POSTGRES_DB'),
            'user' => getenv('POSTGRES_USER'),
            'password' => base64_decode(getenv('POSTGRES_PASSWORD')),
            'schema' => getenv('POSTGRES_SCHEMA')
        ];
    }
}