## Context

См. proposal.md — Why и specs/qa — требование «Раздельные каталоги».

Сейчас контексты грузятся через Composer `App\Tests\` → `tests/` и FoB-сервисы
`config/services_test.yaml`. Behat default autoload (`features/bootstrap`, PSR-0)
не используется. Фикстуры Phing и generate-secret лежат в общем `tests/tools/`.
`tests/bootstrap.php` — test env autoload (PHPUnit `bootstrap=` и FoB
`bootstrap:`); его не переносим.

Границы: слой QA (каталоги `tests/` / `features/`), контейнер `when@test` (FoB
сервисы контекстов), Composer autoload-dev, PHPStan/Rector, процессные входы
агента (skills, AGENTS, `openspec/process.md`). Продуктовый `src/` и E2E-контур
не затрагиваются.

**ADR:** не нужен — раскладка тестовых артефактов и namespace контекстов не
фиксируют выбор технологии или границу продукта.

**Brownfield-поведение:** не применяется. Наблюдаемые сценарии PHPUnit/Behat не
меняются, меняются только пути и договорённость для агента.

## Goals / Non-Goals

**Goals:**

- Один инвариант без оговорок: `features/` — Behat, `tests/` — PHPUnit.
- Контексты в дефолтном каталоге Behat, с Symfony-именами `App\Behat\`.
- Фикстуры только у владельца; Gherkin не указывает в `tests/`.
- PHPStan/Rector видят классы контекстов.

**Non-Goals (design-level):**

- Vanilla Behat без namespace / без FoB-сервисов.
- Перенос `tests/bootstrap.php` или переименование в «preloader».
- Отдельный PSR-0 layout `features/bootstrap/App/Behat/…`.

## Decisions

1. **Namespace `App\Behat\` + Composer PSR-4, не Behat autoload**
   - `autoload-dev`: `"App\\Behat\\": "features/bootstrap/"`.
   - `behat.yml.dist`: `App\Behat\PhingDotEnvContext`, `App\Behat\HomepageContext`.
   - FoB по-прежнему резолвит контекст как сервис; Mink initializer без изменений.
   - *Отклонено:* классы без namespace (A) — ломает autowire и привычный PSR-4.
   - *Отклонено:* сохранить `App\Tests\Behat` — каталог Behat-ный, имя врёт про PHPUnit.
   - Behat `ClassLoader::add()` — PSR-0; на него для `App\Behat\` не опираемся.

2. **FoB resource с exclude фикстур**
   - `config/services_test.yaml`: `App\Behat\` → `../features/bootstrap/`,
     `exclude: ../features/bootstrap/fixtures/`.
   - Иначе контейнер попытается зарегистрировать Phing `autoload.php`.

3. **Gherkin: путь относительно `features/bootstrap/`**
   - Шаг: `When я запускаю phing из каталога "fixtures/tools/<dir>"`.
   - Контекст: `__DIR__.'/'.$dir` (не корень репозитория).
   - *Отклонено:* короткое имя `<dir>` — слабее копируется конвенция `fixtures/`.
   - *Отклонено:* путь от корня `features/bootstrap/fixtures/…` — снова учит FS-layout
     в сценарии и ломается при следующем переезде.

4. **Фикстуры `./fixtures/` у владельца**
   - Конвенция — каталог `fixtures/` рядом с `*Context.php` / `*Test.php`.
   - Phing: `features/bootstrap/fixtures/tools/{prefix-mirror,dump-overrides-env,test-env-overlay,missing-bootenv,autoload.php,autoload-without-bootenv.php}`.
   - generate-secret: `tests/Command/fixtures/tools/{cases,console}/` — общее на
     оба `*Test.php` в `tests/Command/`; обёртку `generate-secret/` не сохраняем.
   - PHPUnit резолвит `__DIR__.'/fixtures/…'`. `secrets/mysql-password.twig`
     остаётся шаблоном репозитория, не фикстурой.
   - `tests/tools/` и `tests/Behat/` удаляются.

5. **Относительные require в Phing autoload**
   - Сейчас `tests/tools/autoload.php` → `../../vendor` (2 уровня).
   - После переезда `features/bootstrap/fixtures/tools/` → `../../../../vendor`
     и `../../../../tools/dotEnvPropertyTask.php` (4 уровня).
   - `build.xml` с `../autoload.php` не меняется (соседство сценария и autoload).

6. **PHPStan / Rector**
   - В paths добавить `features/bootstrap`.
   - Rector: skip `features/bootstrap/fixtures` (Phing-скрипты с `$this` как
     AutoloaderTask). PHPStan может их видеть; существующие `phpstan-ignore` сохранить.
   - CS Fixer уже обходит репозиторий целиком — отдельный path не нужен.

7. **Имена в скиллах**
   - `tests/bootstrap.php` называть **test env autoload**, не prepend/preloader.
   - `features/bootstrap/` — каталог контекстов Behat.
   - Убрать из `tests/AGENTS.md` и `behat-tests` исключение про `tests/Behat/`.

## Risks / Trade-offs

- **[Risk] Неверный depth в `autoload.php` → Behat Phing падает** → явный
  пересчёт `../../../../`; прогон `make behat` на `phing_dotenv_property_task.feature`.
- **[Risk] FoB подхватывает PHP из `fixtures/` как сервисы** → `exclude` в
  `services_test.yaml`.
- **[Risk] Rector ломает Phing-фикстуры** → skip каталога `fixtures`.
- **[Risk] Агент снова кладёт контекст в `tests/`** → этот change обновляет
  skills/AGENTS/process в том же PR, что и перенос файлов.
- **Trade-off:** Composer PSR-4 вместо stock Behat autoload. Каталог дефолтный,
  загрузка — Symfony-идиома; это цена живого FoB.

## Migration Plan

1. Добавить PSR-4 `App\Behat\`, `composer dump-autoload` в Docker.
2. Перенести контексты и фикстуры (`git mv`), поправить namespace, пути,
   `behat.yml.dist`, `services_test.yaml`, PHPUnit-тесты, Gherkin.
3. Расширить paths PHPStan/Rector; skip Rector для фикстур.
4. Обновить process/AGENTS/skills.
5. Прогон: `make test`, `make behat`, `make behat-e2e`.
6. Откат: revert коммита change (нет миграции данных).
