# PhpStorm и Cursor

**PhpStorm** — IDE на платформе JetBrains; агент **Cursor** в нём подключается по протоколу **ACP** ([Agent Client Protocol](https://agentclientprotocol.com/)), без необходимости открывать проект только в редакторе Cursor. Официальное описание процесса и требований — в документации Cursor:

- [JetBrains (интеграция агента Cursor)](https://cursor.com/docs/integrations/jetbrains)

## Кратко по шагам

1. Убедиться в [предварительных условиях](https://cursor.com/docs/integrations/jetbrains#prerequisites): платный [план Cursor](https://cursor.com/docs/models-and-pricing), JetBrains IDE **2025.1+** с плагином [AI Assistant](https://plugins.jetbrains.com/plugin/22282-ai-assistant).
2. Открыть панель **AI Chat** (правая боковая панель или **View → Tool Windows → AI Chat**).
3. В списке провайдеров агентов выбрать **Add Agent from Registry**, найти и установить **Cursor**.
4. Пройти аутентификацию и выбрать Cursor провайдером агента.
5. Отправлять запросы в **AI Chat** — агент читает проект, правит файлы и может выполнять команды в терминале IDE.

Подробности, возможности (модели, индексация кодовой базы, правки файлов, терминал) и [стоимость](https://cursor.com/docs/integrations/jetbrains#pricing) — на странице выше.

## Дополнительно

- **Терминал без JetBrains:** тот же агент доступен из CLI — [обзор Cursor CLI](https://cursor.com/docs/cli/overview) (`agent`, режимы Agent / Plan / Ask).
- **Переезд с JetBrains на Cursor как основную IDE:** [Migrate from JetBrains](https://cursor.com/help/getting-started/migrate-jetbrains).

Правила и скиллы этого репозитория (каталог `.cursor/`) по-прежнему относятся к работе с кодом; при открытии того же клона в PhpStorm с агентом Cursor используйте их по договорённости команды.
