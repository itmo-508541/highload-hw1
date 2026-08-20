# migrations/

Doctrine-миграции — **только для данных** (INSERT, UPDATE, DELETE).

Структуру таблиц и индексы менять через PHP-атрибуты в `src/Entity/*.php`
(Doctrine auto-schema). Не создавать миграции для DDL.
