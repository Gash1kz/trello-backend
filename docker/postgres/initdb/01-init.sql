-- Скрипты в этой папке выполняются один раз при создании пустого тома PostgreSQL.
-- База данных и пользователь создаются автоматически из POSTGRES_DB / POSTGRES_USER
-- (см. docker/compose.yaml), поэтому CREATE DATABASE здесь не нужен.
-- Схема создаётся миграциями: make migrate.

SELECT 1;
