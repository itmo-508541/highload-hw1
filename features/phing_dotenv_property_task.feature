Feature: Phing-задача symfonyenv (зеркало SYMFONY_DOTENV_VARS)
    Чтобы Phing видел те же переменные окружения, что и приложение Symfony
    Как сопровождающий репозиторий
    Я хочу, чтобы symfonyenv копировал в свойства Phing список переменных, загруженных через Symfony Dotenv::bootEnv

    Scenario: Префикс зеркалит ключи из .env в свойства Phing
        When я запускаю phing из каталога "fixtures/tools/prefix-mirror"
        Then команда phing должна завершиться успешно
        And вывод должен содержать "[prefix] myapp.FIXTURE_HOST=127.0.0.1"
        And вывод должен содержать "[prefix] myapp.FIXTURE_PORT=9000"

    Scenario: .env.local.php перекрывает значения из .env (как bootEnv в приложении)
        When я запускаю phing из каталога "fixtures/tools/dump-overrides-env"
        Then команда phing должна завершиться успешно
        And вывод должен содержать "[dump] dotenv.FIXTURE_VALUE=from_dump"
        And вывод должен содержать "[dump] dotenv.APP_ENV=dev"

    Scenario: При -Dapp.env=test читается цепочка .env + .env.test
        When я запускаю phing из каталога "fixtures/tools/test-env-overlay" со свойством "app.env" равным "test"
        Then команда phing должна завершиться успешно
        And вывод должен содержать "[overlay] dotenv.APP_ENV=test"
        And вывод должен содержать "[overlay] dotenv.FIXTURE_VALUE=from_test"

    Scenario: Если bootstrap не вызвал bootEnv, задача завершает сборку с понятной ошибкой
        When я запускаю phing из каталога "fixtures/tools/missing-bootenv"
        Then команда phing должна завершиться с ошибкой
        And вывод должен содержать "SYMFONY_DOTENV_VARS"
