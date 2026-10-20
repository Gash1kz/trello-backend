<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Фабрика логов Monolog.
 *
 * Создаёт логгеры с ротацией по дням, JSON-форматом и обработчиками:
 * - UidProcessor — уникальный ID запроса
 * - PsrLogMessageProcessor — стандартное сообщение
 */
class LoggerFactory
{
    /** @var string путь к директории с логами */
    private static string $logDir;

    /** @var array<string, Logger> кэш логгеров */
    private static array $loggers = [];

    /**
     * Инициализировать базовый путь к логам.
     */
    public static function init(string $logDir): void
    {
        self::$logDir = rtrim($logDir, '/') . '/';

        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0777, true);
        }
    }

    /**
     * Получить (или создать) логгер по каналу.
     *
     * @param string $channel имя канала (app, http, etc.)
     * @param string $level уровень логирования (debug, info, warning, error)
     * @return Logger
     */
    public static function get(string $channel = 'app', string $level = 'debug'): Logger
    {
        if (isset(self::$loggers[$channel])) {
            return self::$loggers[$channel];
        }

        $logger = new Logger($channel);

        // Обработчик: запись в файл с ротацией по дням (<channel>-YYYY-MM-DD.log)
        $handler = new RotatingFileHandler(self::$logDir . $channel . '.log', 30, self::monologLevel($level));
        $handler->setFormatter(new JsonFormatter());

        // Процессор: уникальный ID запроса
        $logger->pushProcessor(new UidProcessor());

        // Процессор: стандартное PSR-сообщение
        $logger->pushProcessor(new PsrLogMessageProcessor());

        $logger->pushHandler($handler);

        self::$loggers[$channel] = $logger;

        return $logger;
    }

    /**
     * Получить логгер для HTTP-запросов (канал "http").
     */
    public static function http(string $level = 'info'): Logger
    {
        return self::get('http', $level);
    }

    /**
     * Получить логгер приложения (канал "app").
     */
    public static function app(string $level = 'debug'): Logger
    {
        return self::get('app', $level);
    }

    /**
     * Преобразовать строковый уровень в константу Monolog.
     */
    private static function monologLevel(string $level): int
    {
        return match (strtolower($level)) {
            'emergency' => Logger::EMERGENCY,
            'alert'     => Logger::ALERT,
            'critical'  => Logger::CRITICAL,
            'error'     => Logger::ERROR,
            'warning'   => Logger::WARNING,
            'notice'    => Logger::NOTICE,
            'info'      => Logger::INFO,
            'debug'     => Logger::DEBUG,
            default     => Logger::DEBUG,
        };
    }
}
