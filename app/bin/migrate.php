#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Database;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;

// 1. Загружаем bootstrap (autoload, .env, Config)
require __DIR__ . '/../bootstrap.php';

$migrationsPath = dirname(__DIR__, 2) . '/database/migrations';
$command = $argv[1] ?? 'help';

// Команды, не требующие подключения к БД
if ($command === 'make') {
    $name = $argv[2] ?? null;
    if (!$name || !preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
        fwrite(STDERR, "Использование: php app/bin/migrate.php make create_table_name (snake_case)\n");
        exit(1);
    }

    // Следующий порядковый номер: 001, 002, ...
    $last = 0;
    foreach (glob($migrationsPath . '/*.php') ?: [] as $file) {
        $last = max($last, (int) basename($file));
    }
    $fileName = sprintf('%03d_%s.php', $last + 1, $name);
    $filePath = $migrationsPath . '/' . $fileName;

    $table = preg_match('/^create_(.+)_table$/', $name, $m) ? $m[1] : $name;

    $stub = <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class extends Migration {
    public function up(): void
    {
        Capsule::schema()->create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('{$table}');
    }
};

PHP;

    file_put_contents($filePath, $stub);
    echo "Миграция создана: {$filePath}\n";
    exit(0);
}

if (!in_array($command, ['migrate', 'status', 'rollback', 'reset', 'fresh'], true)) {
    echo "Использование: php app/bin/migrate.php <команда> [аргументы]\n\n";
    echo "Команды:\n";
    echo "  migrate      - применить все ожидающие миграции\n";
    echo "  status       - показать статус миграций\n";
    echo "  rollback     - откатить последнюю группу миграций\n";
    echo "  reset        - откатить все миграции\n";
    echo "  fresh        - откатить все и применить миграции заново\n";
    echo "  make <name>  - создать новую миграцию (пример: create_posts_table)\n";
    exit($command === 'help' ? 0 : 1);
}

// 2. Подключаем Eloquent и собираем мигратор
Database::boot();

$resolver = Database::manager();
$repository = new DatabaseMigrationRepository($resolver, 'migrations');
$migrator = new Migrator($repository, $resolver, new Filesystem());

// Служебная таблица migrations
if (!$repository->repositoryExists()) {
    $repository->createRepository();
}

// 3. Выполняем команду
switch ($command) {
    case 'migrate':
        $ran = $migrator->run($migrationsPath);
        if ($ran === []) {
            echo "Нечего применять.\n";
        }
        foreach ($ran as $file) {
            echo 'Применена: ' . $migrator->getMigrationName($file) . "\n";
        }
        break;

    case 'status':
        $ran = $repository->getRan();
        foreach ($migrator->getMigrationFiles($migrationsPath) as $name => $file) {
            echo (in_array($name, $ran, true) ? '[YES] ' : '[NO]  ') . $name . "\n";
        }
        break;

    case 'rollback':
        $rolledBack = $migrator->rollback($migrationsPath);
        foreach ($rolledBack as $file) {
            echo 'Откачена: ' . $migrator->getMigrationName($file) . "\n";
        }
        if ($rolledBack === []) {
            echo "Нечего откатывать.\n";
        }
        break;

    case 'reset':
        $rolledBack = $migrator->reset([$migrationsPath]);
        echo 'Откачено миграций: ' . count($rolledBack) . "\n";
        break;

    case 'fresh':
        $migrator->reset([$migrationsPath]);
        $ran = $migrator->run($migrationsPath);
        echo 'База сброшена, применено миграций: ' . count($ran) . "\n";
        break;
}
