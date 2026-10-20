<?php

declare(strict_types=1);

use App\Config;
use App\Database;
use App\Http\RequestLogger;
use App\Logging\LoggerFactory;
use App\Models\Board;
use App\Models\User;

// 1. Загружаем bootstrap (dotenv, Config)
require __DIR__ . '/../app/bootstrap.php';

// 2. Инициализируем логирование (папка storage/logs)
LoggerFactory::init(__DIR__ . '/../app/storage/logs');

// 3. Подключаем Eloquent ORM
Database::boot();

// 4. Хелпер: отправить JSON-ответ
function jsonResponse(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// 5. Хелпер: получить тело запроса как массив
function getJsonBody(): ?array
{
    $input = file_get_contents('php://input');
    if ($input === '' || $input === false) {
        return null;
    }
    $decoded = json_decode($input, true);
    return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
}

// 6. Маршрутизация
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if ($uri === '') {
    $uri = '/';
}

// 7. Начинаем перехват вывода, чтобы залогировать тело ответа
ob_start();

$requestLogger = new RequestLogger(
    LoggerFactory::http((string) Config::get('app.log_level', 'info')),
    (int) (getenv('LOG_BODY_MAX_BYTES') ?: Config::get('app.log_body_max', 0))
);

// 8. Обработка маршрутов
try {
    $matches = [];

    switch (true) {
        // GET / — информация о приложении
        case $method === 'GET' && $uri === '/':
            jsonResponse(200, [
                'app'     => Config::get('app.name'),
                'version' => '1.0.0',
                'env'     => Config::env(),
                'routes'  => [
                    'GET /health'     => 'Проверка БД',
                    'GET /users'      => 'Список пользователей',
                    'POST /users'     => 'Создать пользователя (JSON: name, email)',
                    'GET /users/{id}' => 'Пользователь по ID',
                    'GET /boards'     => 'Список досок',
                    'POST /boards'    => 'Создать доску (JSON: title, owner_id)',
                ],
            ]);
            break;

        // GET /health — проверка подключения к БД
        case $method === 'GET' && $uri === '/health':
            try {
                Database::connection()->select('SELECT 1 as health');
                jsonResponse(200, ['status' => 'ok', 'database' => 'connected']);
            } catch (\Throwable $e) {
                jsonResponse(500, ['status' => 'error', 'database' => 'disconnected', 'error' => $e->getMessage()]);
            }
            break;

        // GET /users — список пользователей
        case $method === 'GET' && $uri === '/users':
            jsonResponse(200, ['users' => User::select('id', 'name', 'email', 'created_at')->get()]);
            break;

        // POST /users — создать пользователя
        case $method === 'POST' && $uri === '/users':
            $data = getJsonBody();
            if ($data === null || !isset($data['name'], $data['email'])
                || !is_string($data['name']) || !is_string($data['email'])
            ) {
                jsonResponse(400, ['error' => 'Требуются поля name и email']);
                break;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                jsonResponse(422, ['error' => 'Некорректный email']);
                break;
            }

            if (User::where('email', $data['email'])->exists()) {
                jsonResponse(422, ['error' => 'Пользователь с таким email уже существует']);
                break;
            }

            // Пароль опционален: если не передан, ставим случайный (вход в API пока не реализован)
            $plainPassword = is_string($data['password'] ?? null) && $data['password'] !== ''
                ? $data['password']
                : bin2hex(random_bytes(16));

            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => password_hash($plainPassword, PASSWORD_DEFAULT),
            ]);

            jsonResponse(201, [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]);
            break;

        // GET /users/{id}
        case $method === 'GET' && preg_match('#^/users/(\d+)$#', $uri, $matches) === 1:
            $user = User::find((int) $matches[1]);
            if (!$user) {
                jsonResponse(404, ['error' => 'Пользователь не найден']);
                break;
            }
            jsonResponse(200, ['user' => $user]);
            break;

        // GET /boards — список досок
        case $method === 'GET' && $uri === '/boards':
            jsonResponse(200, ['boards' => Board::with('owner:id,name')->get()]);
            break;

        // POST /boards — создать доску
        case $method === 'POST' && $uri === '/boards':
            $data = getJsonBody();
            if ($data === null || !isset($data['title'], $data['owner_id'])
                || !is_string($data['title']) || !is_numeric($data['owner_id'])
            ) {
                jsonResponse(400, ['error' => 'Требуются поля title и owner_id']);
                break;
            }

            if (!User::find((int) $data['owner_id'])) {
                jsonResponse(422, ['error' => 'Владелец (owner_id) не найден']);
                break;
            }

            $board = Board::create([
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'owner_id'    => (int) $data['owner_id'],
                'is_public'   => (bool) ($data['is_public'] ?? false),
            ]);

            jsonResponse(201, [
                'id'    => $board->id,
                'title' => $board->title,
            ]);
            break;

        default:
            jsonResponse(404, ['error' => 'Маршрут не найден']);
    }
} catch (\Throwable $e) {
    // Сбрасываем частично выведенный ответ и отдаём JSON с ошибкой
    ob_clean();
    jsonResponse(500, ['error' => $e->getMessage()]);
}

// 9. Завершаем перехват вывода
$responseBody = (string) ob_get_clean();
$responseCode = http_response_code() ?: 200;

// 10. Логируем HTTP-запрос и ответ (включая тело ответа) через Monolog
$requestLogger->finish(
    $responseCode,
    headers_list(),
    $responseBody,
    $requestLogger->getRequestId()
);

// 11. Выводим ответ клиенту
echo $responseBody;
