<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use Dotenv\Dotenv;

// 1. Загружаем переменные окружения из .env (если файл существует).
//    createUnsafeImmutable заполняет в том числе getenv() (через putenv),
//    а конфиги и Crypto читают значения именно через getenv().
$basePath = dirname(__DIR__);
if (file_exists($basePath . '/.env')) {
    Dotenv::createUnsafeImmutable($basePath)->safeLoad();
}

// 2. Определяем окружение
$appEnv = getenv('APP_ENV') ?: 'development';

// 3. Инициализируем статический конфиг (загружает APP_KEY и проверяет его)
Config::init($appEnv);

// 4. Экспортируем часто используемые значения для удобства
return [
    'env'      => $appEnv,
    'config'   => Config::class,
    'basePath' => $basePath . '/',
];
