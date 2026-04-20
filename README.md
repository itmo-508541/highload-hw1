# Symfony backend

Проект backend на [Symfony 7.4 / PHP 8.4](docs/00-symfony.md) с локальной разработкой в Docker и деплоем через GitLab CI/CD.

## Технологический стек

- PHP `8.4`
- Symfony `7.4.*`
- MySQL `8.0`
- Docker / Docker Compose
- Composer
- Phing (инициализация, подготовка storage, миграции, deploy-команды)
- QA-инструменты: PHP CS Fixer, PHPStan, Rector, PHPUnit, Behat

## Быстрый старт

```bash
make up
make init
```

Установка и обновление зависимостей (Composer в контейнере `php`):

```bash
make deps
```

Проверки перед commit:

```bash
make qa
```

**Важно:** команды PHP, Composer, Symfony Console, PHPUnit, Behat, Phing и инструменты QA выполняйте **только** в сервисе `php` (контейнеры должны быть запущены). Подробности цикла разработки и ссылка на технический шаг — в [`docs/00-symfony.md`](docs/00-symfony.md).
