# Trello Backend

Серверное веб-приложение для управления задачами по принципу Trello/Kanban.
Реализовано по требованиям двух домашних заданий:

- **ДЗ 1:** скелет на GitHub, Composer, Eloquent ORM + мигратор, Monolog, логирование входящих HTTP-запросов с телом ответа в `index.php`.
- **ДЗ 2:** статический конфиг с зашифрованными значениями (AES-256-GCM), схема БД из 4 таблиц через миграции, Docker-окружение (nginx + php-fpm + PostgreSQL), секреты не в git.

## Технологический стек

| Компонент | Технология |
|---|---|
| Backend | PHP 8.2+ |
| ORM | Illuminate Database (Eloquent) 12.x |
| Logging | Monolog 3.x |
| Database | PostgreSQL 18 |
| Encryption | OpenSSL AES-256-GCM |
| Infrastructure | Docker, Docker Compose |
| Dependency mgmt | Composer |

## Быстрый запуск (Docker)

```bash
# 1. Скопировать пример окружения
cp .env.example .env
# (при необходимости отредактировать .env)

# 2. Собрать и запустить контейнеры
make up

# 3. Установить зависимости PHP
make composer

# 4. Применить миграции базы данных
make migrate

# Приложение будет доступно по адресу http://127.0.0.1:8080/
```

## Основные команды

```bash
make help            # справка по командам
make up              # запуск контейнеров
make down            # остановка
make migrate         # применить миграции
make migrate-status  # статус миграций
make migrate-rollback# откат последней миграции
make migrate-fresh   # полный сброс и повторное применение миграций
make logs            # просмотр логов контейнеров
make psql            # подключиться к БД
```

## Структура приложения

```
app/
  bootstrap.php           # инициализация приложения, загрузка конфига
  config/
    app.php               # статический конфиг приложения
    database.php          # конфиг подключения к БД
  src/
    Crypto.php            # шифрование/дешифрование (openssl_* AES-256-GCM)
    Config.php            # статический конфиг с поддержкой зашифрованных значений
    Database.php          # подключение Eloquent (Capsule)
    Logging/
      LoggerFactory.php   # фабрика логов Monolog
    Http/
      RequestLogger.php   # логирование входящих HTTP-запросов с телом ответа
    Models/
      User.php            # модель пользователя
      Board.php           # модель доски
      BoardColumn.php     # модель колонки доски
      Card.php            # модель карточки задачи
  bin/
    migrate.php           # мигратор (migrate, status, rollback, reset, fresh, make)
    crypto.php            # generate-key / encrypt / decrypt для конфига
  storage/
    logs/                 # логи Monolog (ротация daily)
database/
  migrations/             # миграции через Eloquent
public/
  index.php               # точка входа: маршруты + логирование запросов
  health.php              # проверка состояния БД
  router.php              # маршрутизатор для встроенного сервера PHP
docker/
  compose.yaml            # оркестрация контейнеров
  nginx/                  # веб-сервер и прокси к php-fpm
  php/                    # PHP-FPM + Composer + расширения
  postgres/               # PostgreSQL + init-скрипты
.env                      # переменные окружения (не коммитится)
.env.example              # шаблон переменных окружения
composer.json             # зависимости PHP
Makefile                  # вспомогательные команды
```

## Маршруты (index.php)

| Метод | Путь | Описание |
|---|---|---|
| GET | `/` | Главная страница (информация о приложении) |
| GET | `/health` | Проверка подключения к БД |
| GET | `/users` | Список пользователей |
| POST | `/users` | Создание пользователя (JSON: name, email) |
| GET | `/users/{id}` | Пользователь по ID |
| GET | `/boards` | Список досок |
| POST | `/boards` | Создание доски |

Все входящие запросы и ответы логируются через Monolog в `app/storage/logs/`.
