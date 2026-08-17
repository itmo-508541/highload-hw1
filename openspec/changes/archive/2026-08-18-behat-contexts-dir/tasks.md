## 1. Characterization / regression (до правок кода)

- [x] 1.1 Прогнать `make test` (suite Unit): зафиксировать зелёный
      `GenerateSecretCommandBootEnvTest` и `GenerateSecretCommandConsoleTest`
      (Scenario «Фикстуры рядом с владельцем», слой PHPUnit)
- [x] 1.2 Прогнать `make behat`: зафиксировать зелёный
      `features/phing_dotenv_property_task.feature` (тот же Scenario, слой Behat)
- [x] 1.3 Прогнать `make behat-e2e`: зафиксировать зелёный
      `features/e2e/homepage.feature` + текущий `HomepageContext`
      (Scenario «Расположение артефактов по слоям» — E2E Behat не смешивается
      с Unit)

## 2. Autoload и конфиг

- [x] 2.1 В `composer.json` autoload-dev добавить PSR-4 `"App\\Behat\\": "features/bootstrap/"`
- [x] 2.2 `composer dump-autoload` в Docker-сервисе `php`
- [x] 2.3 В `behat.yml.dist` сменить FQCN на `App\Behat\PhingDotEnvContext` и
      `App\Behat\HomepageContext`; `bootstrap: tests/bootstrap.php` не менять
      (Scenario «test env autoload не подменяет каталог контекстов»)
- [x] 2.4 В `config/services_test.yaml` заменить `App\Tests\Behat\` на `App\Behat\`
      с `resource: '../features/bootstrap/'` и `exclude: '../features/bootstrap/fixtures/'`

## 3. Контексты Behat (Scenario «Расположение артефактов по слоям»)

- [x] 3.1 Перенести `tests/Behat/*Context.php` → `features/bootstrap/` (`git mv`),
      namespace `App\Behat`
- [x] 3.2 Удалить пустой `tests/Behat/`

## 4. Фикстуры и правки тестов (Scenario «Фикстуры рядом с владельцем»)

- [x] 4.1 Перенести Phing-фикстуры в `features/bootstrap/fixtures/tools/`
      (`prefix-mirror`, `dump-overrides-env`, `test-env-overlay`, `missing-bootenv`,
      `autoload.php`, `autoload-without-bootenv.php`); в autoload-скриптах
      поправить require до корня на `../../../../vendor` и
      `../../../../tools/dotEnvPropertyTask.php`
- [x] 4.2 В `PhingDotEnvContext` резолвить каталог шага как `__DIR__.'/'.$dir`
- [x] 4.3 В `features/phing_dotenv_property_task.feature` заменить пути
      `tests/tools/<dir>` на `fixtures/tools/<dir>` (Behat, тот же Scenario)
- [x] 4.4 Перенести generate-secret в `tests/Command/fixtures/tools/{cases,console}/`
      (без обёртки `generate-secret/`)
- [x] 4.5 В `GenerateSecretCommandBootEnvTest` и `GenerateSecretCommandConsoleTest`
      резолвить фикстуры через `__DIR__.'/fixtures/tools/…'` (PHPUnit, тот же
      Scenario); `secrets/mysql-password.twig` не трогать
- [x] 4.6 Удалить `tests/tools/`

## 5. PHPStan и Rector

- [x] 5.1 В `phpstan.dist.neon` добавить path `features/bootstrap`
- [x] 5.2 В `rector.php` добавить path `features/bootstrap` и skip
      `features/bootstrap/fixtures`

## 6. Правила агента (все три Scenario)

- [x] 6.1 Обновить `openspec/process.md`: контексты в `features/bootstrap/`,
      фикстуры в `./fixtures/`, `tests/bootstrap.php` = test env autoload
- [x] 6.2 Обновить `features/AGENTS.md` и `tests/AGENTS.md`: убрать исключение
      про `tests/Behat/`; правило без оговорок (`features/` — Behat, `tests/` —
      PHPUnit)
- [x] 6.3 Обновить skills `behat-tests` и `phpunit-tests` (пути, `App\Behat\`,
      фикстуры, термин test env autoload)

## 7. Проверка после переноса

- [x] 7.1 Убедиться: нет `tests/Behat/` и `tests/tools/`; контексты только в
      `features/bootstrap/`; `tests/bootstrap.php` на месте
      (Scenario «Расположение…» и «test env autoload…»)
- [x] 7.2 `make test` — зелёный (в т.ч. GenerateSecret)
- [x] 7.3 `make behat` — зелёный (`phing_dotenv_property_task.feature`)
- [x] 7.4 `make behat-e2e` — зелёный (`features/e2e/homepage.feature`)
- [x] 7.5 PHPStan и Rector проходят с новым path `features/bootstrap`
