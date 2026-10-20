<?php

declare(strict_types=1);

namespace App\Http;

use Monolog\Logger;

/**
 * Логирование входящих HTTP-запросов и исходящих ответов.
 *
 * Записывает: метод, URI, IP, заголовки, тело запроса, код ответа, заголовки ответа,
 * тело ответа, длительность, уникальный ID запроса.
 * Секретные поля (authorization, cookies, password, token, secret) маскируются.
 */
class RequestLogger
{
    /** @var Logger */
    private Logger $logger;

    /** @var int максимальный размер тела ответа в логе (0 = без ограничения) */
    private int $maxBodyBytes;

    /** @var array<string> заголовки, которые нужно маскировать */
    private array $maskedHeaders = ['authorization', 'cookie', 'set-cookie'];

    /** @var array<string> поля, которые нужно маскировать в теле */
    private array $maskedFields = ['password', 'token', 'secret'];

    /** @var float время начала обработки запроса */
    private float $startTime;

    /** @var string уникальный ID запроса */
    private string $requestId;

    /** @var string тело запроса (сырое) */
    private string $requestBody = '';

    /** @var array заголовки запроса */
    private array $requestHeaders = [];

    public function __construct(Logger $logger, int $maxBodyBytes = 0)
    {
        $this->logger = $logger;
        $this->maxBodyBytes = $maxBodyBytes;
        $this->startTime = microtime(true);
        $this->requestId = uniqid('req_', true);
        $this->captureRequest();
    }

    /**
     * Завершить логирование после формирования ответа.
     *
     * @param int $statusCode HTTP-код ответа
     * @param array $responseHeaders заголовки ответа
     * @param string $responseBody тело ответа
     * @param string $responseId ID из заголовка X-Request-Id (если есть)
     */
    public function finish(
        int $statusCode,
        array $responseHeaders = [],
        string $responseBody = '',
        string $responseId = ''
    ): void {
        $duration = microtime(true) - $this->startTime;

        $entry = [
            'request_id'     => $this->requestId,
            'response_id'    => $responseId ?: $this->requestId,
            'method'         => $_SERVER['REQUEST_METHOD'] ?? '',
            'uri'            => $_SERVER['REQUEST_URI'] ?? '',
            'ip'             => $_SERVER['REMOTE_ADDR'] ?? '',
            'duration_ms'    => round($duration * 1000, 2),
            'status_code'    => $statusCode,
            'request_headers' => $this->maskHeaders($this->requestHeaders),
            'request_body'   => $this->truncate($this->sanitizeBody($this->requestBody)),
            'response_headers' => $this->maskHeaders($responseHeaders),
            'response_body'  => $this->truncate($this->sanitizeBody($responseBody)),
            'timestamp'      => date('c'),
        ];

        $level = $this->logLevelForStatus($statusCode);
        $this->logger->log($level, 'HTTP request processed', $entry);
    }

    /**
     * Получить ID запроса (для передачи в заголовок X-Request-Id).
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * Захватить входящий HTTP-запрос (заголовки и тело).
     */
    private function captureRequest(): void
    {
        $this->requestHeaders = function_exists('getallheaders') ? (getallheaders() ?: []) : [];

        // Тело запроса (php://input)
        $this->requestBody = file_get_contents('php://input') ?: '';
    }

    /**
     * Маскировать чувствительные заголовки.
     */
    private function maskHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            // headers_list() возвращает строки вида "Name: value" — приводим к ассоциативному виду
            if (is_int($key) && is_string($value) && str_contains($value, ':')) {
                [$key, $value] = array_map('trim', explode(':', $value, 2));
            }
            $key = (string) $key;
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $this->maskedHeaders, true)) {
                $result[$key] = '***masked***';
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Маскировать чувствительные поля в JSON-теле.
     */
    private function sanitizeBody(string $body): string
    {
        if ($body === '') {
            return $body;
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $sanitized = $this->maskArrayFields($decoded);
            return json_encode($sanitized, JSON_UNESCAPED_UNICODE);
        }

        return $body;
    }

    /**
     * Рекурсивно замаскировать поля в массиве.
     */
    private function maskArrayFields(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, $this->maskedFields, true) && is_string($value)) {
                $result[$key] = '***masked***';
            } elseif (is_array($value)) {
                $result[$key] = $this->maskArrayFields($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Обрезать тело до максимального размера.
     */
    private function truncate(string $body): string
    {
        if ($this->maxBodyBytes <= 0 || strlen($body) <= $this->maxBodyBytes) {
            return $body;
        }

        return substr($body, 0, $this->maxBodyBytes) . '...[truncated]';
    }

    /**
     * Определить уровень логирования по HTTP-статусу.
     */
    private function logLevelForStatus(int $status): int
    {
        if ($status >= 500) {
            return Logger::ERROR;
        }
        if ($status >= 400) {
            return Logger::WARNING;
        }
        return Logger::INFO;
    }
}
