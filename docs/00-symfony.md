# Приложение Symfony

Этот документ описывает **что** собой представляет стек и ключевые бандлы в проекте.

## Что сделано на этом этапе

Подготовлено приложение на **Symfony** с типовым набором интеграций для API, аутентификации, документации и администрирования, в окружении **PHP 8.4**, **MySQL 8**, **Redis 8** (хранилище сессий), **Docker Compose**, с **Composer**, **Phing** и набором инструментов качества.

### Технологический стек

- PHP `8.4`
- Symfony `7.4.*`
- MySQL `8.0`
- Redis `8`
- Docker / Docker Compose
- Composer
- Phing (инициализация, подготовка миграции, deploy-команды); bootstrap `tools/phing-bootstrap.php` через `<autoloader>` вызывает `Symfony\Component\Dotenv\Dotenv::bootEnv()` так же, как Symfony Runtime, а собственная задача `symfonyenv` (`tools/dotEnvPropertyTask.php`) копирует `$_SERVER['SYMFONY_DOTENV_VARS']` в свойства Phing с префиксом `dotenv`
- QA-инструменты: PHP CS Fixer, PHPStan, Rector, PHPUnit, Behat

### Основные бандлы

- [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle) — RESTful API для приложения
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle) — JSON Web Token аутентификация
- [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) — документация для REST API в формате OpenAPI/Swagger
- [SonataAdminBundle](https://github.com/sonata-project/SonataAdminBundle) — административный CRUD-интерфейс для сущностей Doctrine

## Шаги релизации

- **[Symfony-приложение](symfony/init.md)** — приложение, готовое для начала разработки.
