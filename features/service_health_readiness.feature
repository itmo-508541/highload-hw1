@domain:service_health
Feature: Готовность сервиса
    Чтобы направлять запросы только к готовому экземпляру
    Как оркестратор или зависимый сервис
    Я хочу по GET /health узнать, доступно ли хранилище записей

    Scenario: сервис готов
        When клиент отправляет GET /health
        Then код ответа 200
        And ответ имеет Content-Type application/json

    Scenario: хранилище недоступно
        Given хранилище записей не отвечает
        When клиент отправляет GET /health
        Then код ответа 503
        And ответ имеет Content-Type application/json
