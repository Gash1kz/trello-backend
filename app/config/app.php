<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Конфиг приложения по окружениям
|--------------------------------------------------------------------------
| Окружение выбирается переменной среды APP_ENV (development / production),
| ключ шифрования — переменной APP_KEY (хранится в .env, не коммитится).
| Значения с префиксом "enc:" расшифровываются автоматически в Config::get().
|
| Зашифровать значение:  php app/bin/crypto.php encrypt "my-secret-value"
| Новый ключ APP_KEY:    php app/bin/crypto.php generate-key
*/
return [

    // Общие параметры для всех окружений
    'default' => [
        'name' => getenv('APP_NAME') ?: 'Trello Backend',
        'env'  => getenv('APP_ENV') ?: 'development',
        'port' => (int) (getenv('APP_PORT') ?: 8080),

        // Пример зашифрованного значения (расшифровывается в "demo-secret-value" ключом APP_KEY из .env;
        // при другом APP_KEY его нужно перешифровать).
        'secret_example' => 'enc:bt2Qnt2sYOYvdhviBgOGzrl1mW6soy//B3xGxzlyonX418p8QRvwIdTm4W+4',
    ],

    'development' => [
        'debug'         => true,
        'log_level'     => 'debug',
        'log_body_max'  => 0,
    ],

    'production' => [
        'debug'         => false,
        'log_level'     => 'info',
        'log_body_max'  => 10000,
    ],

];
