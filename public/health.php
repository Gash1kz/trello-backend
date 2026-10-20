<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Database;
use App\Logging\LoggerFactory;

LoggerFactory::init(__DIR__ . '/../app/storage/logs');
Database::boot();

header('Content-Type: application/json; charset=utf-8');

try {
    \Illuminate\Database\Capsule\Manager::connection()->select('SELECT 1 as health');
    echo json_encode([
        'status'     => 'ok',
        'database'   => 'connected',
        'timestamp'  => date('c'),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'   => 'error',
        'database' => 'disconnected',
        'error'    => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
