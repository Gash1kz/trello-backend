<?php

declare(strict_types=1);

namespace App;

/**
 * Статический конфиг приложения с настройками по окружениям и зашифрованными значениями.
 *
 * Файлы app/config/<section>.php возвращают массив вида:
 *   [
 *     'default'     => [...],   // общие параметры (необязательно)
 *     'development' => [...],   // параметры окружения development
 *     'production'  => [...],   // параметры окружения production
 *   ]
 * Для текущего окружения (APP_ENV) параметры окружения накладываются на 'default'.
 *
 * Значения-строки с префиксом "enc:" автоматически дешифруются через Crypto (ключ — APP_KEY).
 * Окружение (APP_ENV) и ключ шифрования (APP_KEY) берутся из переменных среды.
 */
class Config
{
    /** @var array<string, array<string, mixed>> кэш загруженных секций (уже для текущего окружения) */
    private static array $cache = [];

    private static ?Crypto $crypto = null;

    private static string $env = 'development';

    /**
     * Инициализировать Config (вызвать один раз при старте приложения).
     *
     * @throws \RuntimeException если APP_KEY не задан или некорректен
     */
    public static function init(string $env): void
    {
        self::$env = $env;
        self::$cache = [];
        self::$crypto = new Crypto();
    }

    /**
     * Получить параметр по ключу с точками: 'app.name', 'database.connections.pgsql.host'.
     * Если ключ указывает на массив — зашифрованные значения внутри него тоже дешифруются.
     *
     * @throws \RuntimeException при ошибке дешифрования или отсутствии файла секции
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = self::loadSection(array_shift($parts));

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return self::decryptValue($value);
    }

    /**
     * Получить секцию конфига для текущего окружения (без дешифрования).
     *
     * @return array<string, mixed>
     * @throws \RuntimeException если файл конфига не найден или некорректен
     */
    public static function loadSection(string $section): array
    {
        if (isset(self::$cache[$section])) {
            return self::$cache[$section];
        }

        if (!preg_match('/^[a-z0-9_]+$/i', $section)) {
            throw new \RuntimeException("Некорректное имя секции конфига: {$section}");
        }

        $file = __DIR__ . '/../config/' . $section . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("Конфигурационный файл не найден: {$file}");
        }

        $config = require $file;
        if (!is_array($config)) {
            throw new \RuntimeException("Конфиг {$section}.php должен возвращать массив.");
        }

        $resolved = array_replace_recursive(
            (array) ($config['default'] ?? []),
            (array) ($config[self::$env] ?? [])
        );

        return self::$cache[$section] = $resolved;
    }

    public static function env(): string
    {
        return self::$env;
    }

    public static function isDevelopment(): bool
    {
        return self::$env === 'development';
    }

    /**
     * Рекурсивно дешифровать значения с префиксом "enc:".
     */
    private static function decryptValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(static fn ($v) => self::decryptValue($v), $value);
        }

        if (is_string($value) && str_starts_with($value, 'enc:')) {
            return self::crypto()->decrypt(substr($value, 4));
        }

        return $value;
    }

    private static function crypto(): Crypto
    {
        if (self::$crypto === null) {
            throw new \RuntimeException('Config не инициализирован. Вызовите Config::init($env) перед использованием.');
        }
        return self::$crypto;
    }
}
