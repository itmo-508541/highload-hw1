# Capability Index

Индекс реализации capabilities проекта.
Для деталей по конкретной тематике — см. `topics/<name>.yaml`.

## Domains

| Domain | Description | Topics |
|--------|-------------|--------|
| platform | Инварианты платформы: Docker, Makefile, локальный цикл | platform |
| qa | Слои проверки качества: PHPUnit, Behat, E2E, каталоги | qa |
| tooling | Инструменты парного программирования с AI | terra |

## Topics

| Topic | Spec | Summary |
|-------|------|---------|
| platform | specs/platform/spec.md | PHP/QA только в Docker, Makefile как фасад |
| qa | specs/qa/spec.md | Раздельные каталоги тестов, контуры Unit/Behat/E2E |
| terra | specs/terra/spec.md | Сериализованный кеш исследования кодовой базы для передачи между AI-агентами при парном программировании |
