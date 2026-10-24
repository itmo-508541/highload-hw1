@domain:item_storage
Feature: Невалидное тело записи
    Чтобы в хранилище попадали только скалярные значения
    Как клиент сервиса
    Я хочу получать 400 на тело другого вида, и хранилище при этом не меняется

    Scenario Outline: невалидное тело отклоняется
        When клиент отправляет POST /items с Content-Type <тип> и телом:
            """
            <тело>
            """
        Then код ответа 400
        And ответ имеет Content-Type application/json
        And тело ответа — JSON-объект
        And хранилище записей не изменилось

        Examples:
            | набор          | тип                               | тело                     |
            | missing-value  | application/json                  | {}                       |
            | extra-key      | application/json                  | {"value": 1, "extra": 2} |
            | null-value     | application/json                  | {"value": null}          |
            | array-value    | application/json                  | {"value": [1]}           |
            | object-value   | application/json                  | {"value": {"a": 1}}      |
            | invalid-json   | application/json                  | {"value":                |
            | form-body      | application/x-www-form-urlencoded | value=foo                |
            | overflow-float | application/json                  | {"value": 1e400}         |
