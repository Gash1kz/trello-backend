# Trello Backend — учебный проект (PHP + PostgreSQL + Eloquent + Monolog)

Документация по реализации требований двух домашних заданий (ДЗ 1 и ДЗ 2) от преподавателя по веб-разработке.

## 1. Описание проекта

Серверное веб-приложение для управления задачами по принципу Trello/Kanban. Реализовано на чистом PHP 8.5 с использованием Eloquent ORM (Illuminate Database), Monolog для логирования, Docker Compose для инфраструктуры (nginx + php-fpm + PostgreSQL).

## 2. Соответствие требованиям преподавателя

### ДЗ 1 — скелет приложения, Eloquent, миграции, Monolog

| Требование | Реализация | Файл |
|---|---|---|
| Публичный проект в GitHub, скелет с основными библиотеками | `composer.json` с PSR-4 автозагрузкой, зависимостями | `composer.json` |
| Подключение Eloquent ORM | `Database::boot()` через Capsule Manager | `app/src/Database.php` |
| Мигратор с тестовой миграцией | `app/bin/migrate.php` с командами `migrate`, `status`, `rollback`, `reset`, `fresh`, `make` | `app/bin/migrate.php` |
| Тестовая миграция | 4 миграции таблиц + служебная `migrations` | `database/migrations/` |
| Логирование Monolog | `LoggerFactory` — JSON-логи, ротация по дням, `UidProcessor` | `app/src/Logging/LoggerFactory.php` |
| В `index.php` переход на Eloquent | Все маршруты используют Eloquent модели | `public/index.php` |
| Логирование каждого входящего HTTP-запроса **с телом ответа** | `RequestLogger` пишет метод, URI, IP, тело запроса, статус, тело ответа, длительность, ID запроса | `app/src/Http/RequestLogger.php` |

### ДЗ 2 — статический конфиг, шифрование, схема БД, Docker

| Требование | Реализация | Файл |
|---|---|---|
| Статический конфиг с зашифрованными значениями | `Config::get()` автоматически расшифровывает значения с префиксом `enc:` | `app/src/Config.php`, `app/config/app.php` |
| Окружение и ключ шифрования через переменные среды | `APP_ENV`, `APP_KEY` читаются через `getenv()` | `.env`, `.env.example` |
| Файловый конфиг отдаёт массив параметров по окружениям | `app/config/*.php` возвращают массивы с ключами `default`, `development`, `production`; `Config` накладывает параметры текущего `APP_ENV` на `default` | `app/config/`, `app/src/Config.php` |
| Отдельный объект для шифрования/дешифрования | `Crypto` класс с `encrypt()` / `decrypt()` | `app/src/Crypto.php` |
| `openssl_encrypt` / `openssl_decrypt` + AES-256-GCM | `openssl_encrypt(..., 'aes-256-gcm', ...)` | `app/src/Crypto.php` |
| Добавление PHP-расширения в контейнер | `docker/php/Dockerfile` устанавливает `pdo_pgsql`, `pgsql`, `mbstring`, `zip` и др. (`openssl` встроен в образ php) | `docker/php/Dockerfile` |
| Консольные утилиты | `migrate.php` (миграции), `crypto.php` (ключ, шифрование значений конфига) | `app/bin/` |
| Пароли/секреты не коммитим в git | `.env` в `.gitignore`, только `.env.example` | `.gitignore` |
| Схема БД из 4 основных таблиц через миграции | `users`, `boards`, `board_columns`, `cards` | `database/migrations/` |
| Docker-окружение | nginx + php-fpm 8.5 + PostgreSQL 18 | `docker/compose.yaml` |

## 3. Технологический стек

| Компонент | Технология | Версия |
|---|---|---|
| Backend | PHP | 8.5 |
| ORM | Illuminate Database (Eloquent) | 12.x |
| Logging | Monolog | 3.x |
| Database | PostgreSQL | 18 |
| Encryption | OpenSSL AES-256-GCM | — |
| Dependency mgmt | Composer | 2.x |
| Infrastructure | Docker, Docker Compose | — |
| Web server | nginx | stable-alpine |
| PHP-FPM | php:8.5-fpm | — |
| Dotenv | vlucas/phpdotenv | 5.x |

## 4. Структура проекта

```
trello-backend/
├── .env                         # Переменные окружения (НЕ коммитится)
├── .env.example                 # Шаблон переменных окружения
├── .gitignore                   # Исключения из git
├── Makefile                     # Вспомогательные команды
├── README.md                    # Описание проекта
├── composer.json                # Зависимости PHP
├── composer.lock                # Зафиксированные версии
├── vendor/                      # Установленные пакеты Composer
│
├── app/
│   ├── bootstrap.php            # Инициализация: dotenv, Config, загрузка
│   ├── config/
│   │   ├── app.php              # Статический конфиг приложения
│   │   └── database.php         # Конфиг подключения к БД
│   ├── bin/
│   │   ├── migrate.php          # Мигратор (migrate, status, rollback, reset, fresh, make)
│   │   └── crypto.php           # generate-key / encrypt / decrypt для конфига
│   ├── src/
│   │   ├── Crypto.php           # Шифрование AES-256-GCM (openssl_*)
│   │   ├── Config.php           # Статический конфиг с зашифрованными значениями
│   │   ├── Database.php         # Подключение Eloquent ORM (Capsule)
│   │   ├── Http/
│   │   │   └── RequestLogger.php  # Логирование HTTP-запросов с телом ответа
│   │   ├── Logging/
│   │   │   └── LoggerFactory.php  # Фабрика логов Monolog
│   │   └── Models/
│   │       ├── User.php         # Модель пользователя
│   │       ├── Board.php        # Модель доски
│   │       ├── BoardColumn.php  # Модель колонки доски
│   │       └── Card.php         # Модель карточки задачи
│   └── storage/
│       └── logs/
│           └── .gitkeep       # Placeholder для логов Monolog
│
├── database/
│   └── migrations/
│       ├── 001_create_users_table.php
│       ├── 002_create_boards_table.php
│       ├── 003_create_board_columns_table.php
│       └── 004_create_cards_table.php
│
├── docker/
│   ├── compose.yaml             # Оркестрация контейнеров
│   ├── nginx/
│   │   ├── Dockerfile
│   │   └── conf.d/
│   │       └── default.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── php.ini
│   │   └── www.conf
│   └── postgres/
│       ├── Dockerfile
│       ├── postgresql.conf
│       └── initdb/
│           └── 01-init.sql
│
└── public/
    ├── index.php                # Точка входа: маршруты + логирование запросов
    ├── health.php               # Проверка состояния БД
    └── router.php               # Маршрутизатор для php -S
```

## 5. Модели данных (схема БД)

### users
| Поле | Тип | Описание |
|---|---|---|
| id | bigint, PK | Идентификатор |
| name | string | Имя пользователя |
| email | string, unique | Email (уникальный) |
| password | string | Хеш пароля |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### boards
| Поле | Тип | Описание |
|---|---|---|
| id | bigint, PK | Идентификатор |
| title | string | Название доски |
| description | text, nullable | Описание |
| owner_id | bigint, FK→users | Владелец доски |
| is_public | boolean | Публичная доска |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### board_columns
| Поле | Тип | Описание |
|---|---|---|
| id | bigint, PK | Идентификатор |
| board_id | bigint, FK→boards | Доска |
| title | string | Название колонки |
| position | integer | Позиция сортировки |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### cards
| Поле | Тип | Описание |
|---|---|---|
| id | bigint, PK | Идентификатор |
| board_column_id | bigint, FK→board_columns | Колонка |
| board_id | bigint, FK→boards | Доска |
| user_id | bigint, FK→users, nullable | Ответственный пользователь |
| title | string | Название карточки |
| description | text, nullable | Описание |
| position | integer | Позиция сортировки |
| status | string | Статус (todo, in_progress, done) |
| created_at | timestamp | Дата создания |
| updated_at | timestamp | Дата обновления |

### migrations (служебная)
| Поле | Тип | Описание |
|---|---|---|
| id | bigint, PK | Идентификатор |
| migration | string | Имя файла миграции |
| batch | integer | Номер пакета (batch) |

## 6. API эндпоинты (public/index.php)

| Метод | Путь | Описание | Тело запроса |
|---|---|---|---|
| GET | `/` | Информация о приложении и список маршрутов | — |
| GET | `/health` | Проверка подключения к БД | — |
| GET | `/users` | Список пользователей | — |
| POST | `/users` | Создание пользователя | `{"name": "...", "email": "..."}` |
| GET | `/users/{id}` | Пользователь по ID | — |
| GET | `/boards` | Список досок | — |
| POST | `/boards` | Создание доски | `{"title": "...", "owner_id": 1}` |

### Примеры запросов

**Создание пользователя:**
```powershell
Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8080/users -ContentType 'application/json' -Body '{"name":"Ivan","email":"ivan@example.com"}'
```

**Получение списка пользователей:**
```powershell
Invoke-RestMethod -Uri http://127.0.0.1:8080/users
```

**Проверка здоровья БД:**
```powershell
Invoke-RestMethod -Uri http://127.0.0.1:8080/health
```

**Создание доски:**
```powershell
Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8080/boards -ContentType 'application/json' -Body '{"title":"Моя доска","owner_id":1}'
```

## 7. Логирование (Monolog)

Все входящие HTTP-запросы логируются в `app/storage/logs/http-YYYY-MM-DD.log` в формате JSON (`RotatingFileHandler`, хранится 30 дней). Данные запроса/ответа лежат в поле `context`.

Пример записи лога (одна строка JSON, здесь отформатирована):
```json
{
  "message": "HTTP request processed",
  "context": {
    "request_id": "req_68a1b2c3d4e5f6.12345678",
    "response_id": "req_68a1b2c3d4e5f6.12345678",
    "method": "POST",
    "uri": "/users",
    "ip": "127.0.0.1",
    "duration_ms": 12.5,
    "status_code": 201,
    "request_headers": { "Content-Type": "application/json" },
    "request_body": "{\"name\":\"Ivan\",\"email\":\"ivan@example.com\"}",
    "response_headers": { "Content-Type": "application/json; charset=utf-8" },
    "response_body": "{\"id\":1,\"name\":\"Ivan\",\"email\":\"ivan@example.com\"}",
    "timestamp": "2026-10-20T14:30:00+03:00"
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "http",
  "datetime": "2026-10-20T14:30:00.123456+03:00",
  "extra": { "uid": "68a1b2c3d4e5f6" }
}
```

Уровень: 2xx/3xx — `INFO`, 4xx — `WARNING`, 5xx — `ERROR`.

Маскируемые поля (заменяются на `***masked***`):
- Заголовки: `authorization`, `cookie`, `set-cookie`
- Поля тела: `password`, `token`, `secret`

## 8. Шифрование конфигурации (Crypto)

Класс `App\Crypto` использует `openssl_encrypt` / `openssl_decrypt` с алгоритмом **AES-256-GCM**.

### Генерация ключа APP_KEY
```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

### Зашифрованные значения в конфиге
В `app/config/app.php` зашифрованные значения помечаются префиксом `enc:`:
```php
'secret_example' => 'enc:<base64(iv . tag . ciphertext)>',
```

При чтении через `Config::get('app.secret_example')` значение автоматически дешифруется (в том числе внутри вложенных массивов, например `Config::get('database.connections.pgsql.password')`).

Параметры по окружениям: файл конфига имеет вид `['default' => [...], 'development' => [...], 'production' => [...]]`; `Config::get('app.debug')` вернёт значение для текущего `APP_ENV`.

Консольная утилита:
```bash
php app/bin/crypto.php generate-key               # новый APP_KEY
php app/bin/crypto.php encrypt "my-secret-value"  # -> enc:... (вставить в конфиг)
php app/bin/crypto.php decrypt enc:...            # расшифровать
```

> Значение `secret_example` зашифровано ключом из локального `.env` (расшифровывается в `demo-secret-value`). При другом `APP_KEY` его нужно перешифровать.

## 9. Миграции (app/bin/migrate.php)

Доступные команды:

```bash
php app/bin/migrate.php migrate        # Применить все миграции
php app/bin/migrate.php status         # Статус миграций
php app/bin/migrate.php rollback       # Откат последней группы
php app/bin/migrate.php reset          # Откат всех миграций
php app/bin/migrate.php fresh          # Сброс и повторное применение
php app/bin/migrate.php make create_posts_table  # Создать новую миграцию
```

Через Makefile:
```bash
make migrate
make migrate-status
make migrate-rollback
make migrate-fresh
make migrate-make name=create_posts_table
```

Makefile вызывает `docker compose --project-directory . -f docker/compose.yaml --env-file .env`, поэтому все `make`-команды запускаются из корня проекта.

## 10. Установка и запуск

### Вариант 1: Встроенный сервер PHP (без Docker)

```powershell
# Перейти в папку проекта
cd C:\Users\metadon\Documents\Default Project\trello-backend

# Запустить встроенный сервер
php -S localhost:8080 -t public public/router.php

# Приложение доступно по адресу http://127.0.0.1:8080/
```

### Вариант 2: Docker Compose

```bash
# Скопировать пример окружения
cp .env.example .env

# Собрать и запустить контейнеры
make up

# Установить зависимости PHP
make composer

# Применить миграции
make migrate

# Приложение доступно по адресу http://127.0.0.1:8080/
```

Основные команды Makefile:
| Команда | Описание |
|---|---|
| `make up` | Запуск контейнеров |
| `make down` | Остановка контейнеров |
| `make composer` | Установка зависимостей PHP |
| `make migrate` | Применение миграций |
| `make migrate-status` | Статус миграций |
| `make migrate-rollback` | Откат миграции |
| `make migrate-fresh` | Полный сброс БД |
| `make logs` | Просмотр логов контейнеров |
| `make psql` | Подключение к PostgreSQL |
| `make clean` | Полная очистка томов |

## 11. Проверка и известные ограничения

Проверено локально (Windows, PHP 8.5.11):
- `composer validate --strict` и `composer dump-autoload` проходят без ошибок;
- все PHP-файлы проходят `php -l`;
- `Config::get('app.secret_example')` расшифровывает значение (`Crypto` + `APP_KEY` из `.env`), `crypto.php encrypt/decrypt` работают;
- встроенный сервер (`php -S`): маршруты `/`, 404, валидация `POST /users` отвечают корректно, в `http-YYYY-MM-DD.log` пишутся метод, URI, фактический статус и тело ответа;
- SQL всех 4 миграций корректно генерируется в режиме `pretend` (PostgreSQL grammar); `migrate.php make` создаёт валидный файл.

**Не проверено** (в среде не было PostgreSQL и Docker):
- реальное применение миграций (`migrate`, `rollback`, `fresh`) и CRUD-маршруты, работающие с БД;
- сборка и запуск Docker Compose (`make up`).

## 12. Что требует действий со стороны пользователя

1. **Запушить на GitHub** — создать публичный репозиторий и запушить код:
   ```bash
   git init
   git add .
   git commit -m "Initial commit: Trello backend skeleton"
   git remote add origin https://github.com/ВАШ_ЮЗЕРНЕЙМ/trello-backend.git
   git push -u origin main
   ```

2. **Запустить Docker** (полноценное развёртывание с PostgreSQL):
   ```bash
   make up && make composer && make migrate
   ```

3. **Проверить эндпоинты** через браузер или curl/PowerShell.
## 13. Ответственный

Проект реализован агентом Sisyphus (OhMyOpenCode) по требованиям пользователя. Весь код написан с нуля, не копирован из репозиториев-примеров, но следует той же архитектуре и стеку технологий.
