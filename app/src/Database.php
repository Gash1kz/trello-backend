<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Подключение Eloquent ORM (Illuminate Database) через Capsule.
 *
 * Читает конфигурацию из app/config/database.php и переменных окружения.
 * Обеспечивает единый доступ к базе данных для моделей и миграций.
 */
class Database
{
    /** @var bool подключение уже инициализировано */
    private static bool $booted = false;

    /** @var Capsule|null экземпляр Capsule */
    private static ?Capsule $capsule = null;

    /**
     * Инициализировать Eloquent Capsule один раз.
     *
     * @throws \Throwable если не удалось подключиться к БД
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        $capsule = new Capsule();

        // Config::get дешифрует значения enc:... (например, пароль БД)
        $defaultConnection = (string) Config::get('database.default', 'pgsql');
        $conn = Config::get('database.connections.' . $defaultConnection);

        if (!is_array($conn)) {
            throw new \RuntimeException("Соединение с БД '{$defaultConnection}' не найдено в конфиге database.php");
        }

        $capsule->addConnection([
            'driver'    => $conn['driver'] ?? 'pgsql',
            'host'      => $conn['host'] ?? '127.0.0.1',
            'port'      => $conn['port'] ?? 5432,
            'database'  => $conn['database'] ?? 'trello',
            'username'  => $conn['username'] ?? 'trello',
            'password'  => $conn['password'] ?? '',
            'charset'   => $conn['charset'] ?? 'utf8',
            'prefix'    => $conn['prefix'] ?? '',
            'schema'    => $conn['schema'] ?? 'public',
            'sslmode'   => $conn['sslmode'] ?? 'prefer',
        ], $defaultConnection);

        // Capsule по умолчанию использует соединение "default" — переключаем на нужное
        $capsule->getDatabaseManager()->setDefaultConnection($defaultConnection);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        self::$capsule = $capsule;
        self::$booted = true;
    }

    /**
     * Получить менеджер соединений (нужен мигратору как ConnectionResolver).
     */
    public static function manager(): \Illuminate\Database\DatabaseManager
    {
        if (!self::$booted) {
            self::boot();
        }
        return self::$capsule->getDatabaseManager();
    }

    /**
     * Получить экземпляр соединения с базой данных.
     *
     * @return \Illuminate\Database\Connection
     * @throws \RuntimeException если Capsule не загружен
     */
    public static function connection(): \Illuminate\Database\Connection
    {
        if (!self::$booted) {
            self::boot();
        }
        return Capsule::connection();
    }

    /**
     * Получить экземпляр схемы (Schema Builder) для создания/изменения таблиц.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function schema(): \Illuminate\Database\Schema\Builder
    {
        return self::connection()->getSchemaBuilder();
    }

    /**
     * Выполнить произвольный SQL-запрос.
     *
     * @param string $sql
     * @param array $bindings
     * @return array результат SELECT-запроса
     */
    public static function query(string $sql, array $bindings = []): array
    {
        return self::connection()->select($sql, $bindings);
    }
}
