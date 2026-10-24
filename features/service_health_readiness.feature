@domain:service_health
Feature: Готовность сервиса
    Чтобы направлять запросы только к готовому экземпляру
    Как оркестратор или зависимый сервис
    Я хочу по GET /health узнать, доступно ли хранилище записей

    Scenario: сервис готов
        When клиент отправляет GET /health
        Then код ответа 200
        And ответ имеет Content-Type application/json
        And тело ответа — JSON-объект

    Scenario Outline: хранилище недоступно
        Given хранилище записей <отказ>
        When клиент отправляет GET /health
        Then код ответа 503
        And ответ имеет Content-Type application/json
        And тело ответа — JSON-объект

        Examples:
            | набор         | отказ                    |
            | ping-fails    | перестало отвечать       |
            | connect-fails | не принимает подключений |
