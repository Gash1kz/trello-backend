<?php

declare(strict_types=1);

/**
 * Маршрутизатор для встроенного веб-сервера PHP (php -S).
 * Перенаправляет все запросы на index.php, кроме статических файлов.
 */

$documentRoot = __DIR__;
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$filePath = $documentRoot . $requestUri;

if ($requestUri !== '/' && is_file($filePath)) {
    return false; // отдаём статический файл как есть
}

require __DIR__ . '/index.php';
