<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Конфиг БД по окружениям
|--------------------------------------------------------------------------
| Пароль по умолчанию хранится в зашифрованном виде (enc:...), его можно
| переопределить переменной среды DB_PASSWORD (так делает docker compose).
*/
return [

    'default' => [
        'default'     => getenv('DB_CONNECTION') ?: 'pgsql',
        'connections' => [
            'pgsql' => [
                'driver'   => 'pgsql',
                'port'     => (int) (getenv('DB_PORT') ?: 5432),
                'database' => getenv('DB_DATABASE') ?: 'trello',
                'username' => getenv('DB_USERNAME') ?: 'trello',
                'password' => getenv('DB_PASSWORD') ?: 'enc:lmk+1VEJXVqqy8nx15ajCLjM1+E3vvy6hbEpMwxKZX2SNnKA80ZNPhqUDw==',
                'charset'  => 'utf8',
                'prefix'   => '',
                'schema'   => 'public',
            ],
        ],
    ],

    'development' => [
        'connections' => [
            'pgsql' => [
                'host'    => getenv('DB_HOST') ?: '127.0.0.1',
                'sslmode' => 'prefer',
            ],
        ],
    ],

    'production' => [
        'connections' => [
            'pgsql' => [
                'host'    => getenv('DB_HOST') ?: 'postgres',
                'sslmode' => getenv('DB_SSLMODE') ?: 'prefer',
            ],
        ],
    ],

];
