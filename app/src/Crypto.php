<?php

declare(strict_types=1);

namespace App;

/**
 * Шифрование и дешифрование конфиденциальных параметров конфигурации.
 *
 * Используется алгоритм AES-256-GCM через openssl_encrypt / openssl_decrypt.
 * Ключ шифрования задаётся через переменную окружения APP_KEY и не попадает в git.
 */
class Crypto
{
    /** Алгоритм шифрования OpenSSL */
    private const CIPHER = 'aes-256-gcm';

    /** Длина ключа в байтах (256 бит) */
    private const KEY_LENGTH = 32;

    /** Длина тега аутентификации GCM */
    private const TAG_LENGTH = 16;

    /** Длина initialization vector (IV/Nonce) для GCM */
    private const IV_LENGTH = 12;

    /** @var string Base64-закодированный ключ из APP_KEY */
    private string $key;

    /** @var bool Флаг того, что ключ загружен и валиден */
    private bool $ready = false;

    /**
     * @throws \RuntimeException если ключ не задан или некорректен
     */
    public function __construct()
    {
        $rawKey = getenv('APP_KEY');
        if ($rawKey === false || $rawKey === '') {
            throw new \RuntimeException('APP_KEY не задан в переменных окружения. Задайте .env и сгенерируйте ключ: php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"');
        }

        $decoded = base64_decode($rawKey, true);
        if ($decoded === false || strlen($decoded) !== self::KEY_LENGTH) {
            throw new \RuntimeException('APP_KEY должен содержать 32 байта в base64 (256 бит).');
        }

        $this->key = $decoded;
        $this->ready = true;
    }

    /**
     * Зашифровать строку.
     *
     * Результат: base64(iv . tag . ciphertext)
     * Префикс "enc:" добавляется на уровне Config, не здесь.
     *
     * @param string $value значение для шифрования
     * @return string base64-закодированное зашифрованное значение (iv + tag + data)
     * @throws \RuntimeException при ошибке шифрования
     */
    public function encrypt(string $value): string
    {
        if (!$this->ready) {
            throw new \RuntimeException('Crypto не инициализирован.');
        }

        $iv = random_bytes(self::IV_LENGTH);

        $encrypted = openssl_encrypt(
            $value,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false || $tag === false) {
            throw new \RuntimeException('Ошибка шифрования значения через openssl_encrypt.');
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Расшифровать строку.
     *
     * Ожидает base64(iv . tag . ciphertext), как возвращает encrypt().
     *
     * @param string $encryptedValue зашифрованное значение (base64)
     * @return string расшифрованное значение
     * @throws \RuntimeException при ошибке дешифрования или неверном формате
     */
    public function decrypt(string $encryptedValue): string
    {
        if (!$this->ready) {
            throw new \RuntimeException('Crypto не инициализирован.');
        }

        $decoded = base64_decode($encryptedValue, true);
        if ($decoded === false) {
            throw new \RuntimeException('Невозможно декодировать base64 зашифрованное значение.');
        }

        $expectedLength = self::IV_LENGTH + self::TAG_LENGTH;
        if (strlen($decoded) <= $expectedLength) {
            throw new \RuntimeException('Неверная длина зашифрованного значения.');
        }

        $iv = substr($decoded, 0, self::IV_LENGTH);
        $tag = substr($decoded, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($decoded, $expectedLength);

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Ошибка дешифрования значения через openssl_decrypt.');
        }

        return $decrypted;
    }

    /**
     * Генерация нового ключа APP_KEY (base64, 32 байта).
     *
     * @return string base64-закодированный ключ
     */
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(self::KEY_LENGTH));
    }

    public function isReady(): bool
    {
        return $this->ready;
    }
}
