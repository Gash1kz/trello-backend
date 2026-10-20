#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Консольная утилита для работы с шифрованием конфигурации.
 *
 * Использование:
 *   php app/bin/crypto.php generate-key        — сгенерировать новый APP_KEY
 *   php app/bin/crypto.php encrypt <значение>  — вернуть строку вида enc:... для вставки в конфиг
 *   php app/bin/crypto.php decrypt <enc:...>   — расшифровать значение
 */

use App\Crypto;

$command = $argv[1] ?? 'help';

if ($command === 'generate-key') {
    require __DIR__ . '/../../vendor/autoload.php';
    echo Crypto::generateKey(), PHP_EOL;
    exit(0);
}

require __DIR__ . '/../bootstrap.php';

$crypto = new Crypto();

switch ($command) {
    case 'encrypt':
        $value = $argv[2] ?? null;
        if ($value === null) {
            fwrite(STDERR, "Использование: php app/bin/crypto.php encrypt <значение>\n");
            exit(1);
        }
        echo 'enc:' . $crypto->encrypt($value), PHP_EOL;
        break;

    case 'decrypt':
        $value = $argv[2] ?? null;
        if ($value === null) {
            fwrite(STDERR, "Использование: php app/bin/crypto.php decrypt <enc:...>\n");
            exit(1);
        }
        if (str_starts_with($value, 'enc:')) {
            $value = substr($value, 4);
        }
        echo $crypto->decrypt($value), PHP_EOL;
        break;

    case 'help':
    default:
        echo "Использование: php app/bin/crypto.php <команда> [аргумент]\n\n";
        echo "Команды:\n";
        echo "  generate-key        - сгенерировать APP_KEY (base64, 32 байта)\n";
        echo "  encrypt <значение>  - зашифровать значение (результат вставляется в конфиг)\n";
        echo "  decrypt <enc:...>   - расшифровать значение\n";
        break;
}
