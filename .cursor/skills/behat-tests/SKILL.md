---
name: behat-tests
description: >
  Пишет и обновляет Behat-сценарии (Gherkin в features/, контексты в
  features/bootstrap/, App\Behat\); прогон — make behat / make behat-e2e.
  Использовать при BDD/приёмочных сценариях, изменении API/UI-потоков, просьбе
  добавить или прогнать Behat, упоминании features/, features/e2e/,
  features/bootstrap/, behat.yml.dist, make behat, make behat-e2e, App\Behat.
---

# Behat

Контекст и конвенции: `features/AGENTS.md`.

## Рабочий цикл

1. Прочитать затронутые OpenSpec specs (если есть активный change); решить,
   нужен ли Behat (приёмка API/потока) или достаточно PHPUnit.
2. Добавить или изменить Gherkin в `features/`, реализовать шаги в контексте
   в `features/bootstrap/` — стиль от соседних контекстов.
3. Новый контекст → зарегистрировать в `behat.yml.dist`.
4. Запустить: `make behat` (default), `make behat-e2e` (E2E), один файл —
   `make behat features/foo.feature`.
5. Исправить падения до зелёного прогона (макс. 3 попытки). Если после
   третьей попытки сценарии не зелёные — остановиться, сообщить пользователю
   о проблеме и ждать указаний.

## Чеклист

- [ ] `.feature` не дублирует OpenSpec specs wholesale
- [ ] Контексты в `features/bootstrap/`, подключены в `behat.yml.dist`
- [ ] Каталоги `openspec/` и `features/` не смешаны
- [ ] `make behat` пройден в Docker
- [ ] Пользователю указаны пути и результат прогона
