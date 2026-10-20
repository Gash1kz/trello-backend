.PHONY: help init up down build composer migrate migrate-status migrate-rollback migrate-fresh migrate-make logs ps psql sh clean

# compose.yaml лежит в docker/, а пути внутри него считаются от корня проекта
DC = docker compose --project-directory . -f docker/compose.yaml --env-file .env

help: ## Показать справку по командам Makefile
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

init: ## Создать .env из .env.example (если его ещё нет)
	@if [ ! -f .env ]; then cp .env.example .env; echo ".env создан: задайте APP_KEY (php app/bin/crypto.php generate-key)"; fi

up: init ## Собрать и запустить контейнеры
	$(DC) up -d --build

down: ## Остановить и удалить контейнеры
	$(DC) down

build: ## Пересобрать образы
	$(DC) build

composer: ## Установить зависимости PHP через composer
	$(DC) exec php composer install

migrate: ## Применить все миграции
	$(DC) exec php php app/bin/migrate.php migrate

migrate-status: ## Показать статус миграций
	$(DC) exec php php app/bin/migrate.php status

migrate-rollback: ## Откатить последнюю группу миграций
	$(DC) exec php php app/bin/migrate.php rollback

migrate-fresh: ## Откатить все и применить миграции заново
	$(DC) exec php php app/bin/migrate.php fresh

migrate-make: ## Создать новую миграцию (использование: make migrate-make name=create_posts_table)
	$(DC) exec php php app/bin/migrate.php make $(name)

logs: ## Показать логи контейнеров
	$(DC) logs -f

ps: ## Показать запущенные контейнеры
	$(DC) ps

psql: ## Подключиться к PostgreSQL через psql
	$(DC) exec postgres sh -c 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"'

sh: ## Зайти в контейнер PHP
	$(DC) exec php sh

clean: ## Удалить тома и контейнеры (полная очистка БД)
	$(DC) down -v
