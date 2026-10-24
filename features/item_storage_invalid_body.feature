@domain:item_storage
Feature: Невалидное тело записи
    Чтобы в хранилище попадали только скалярные значения
    Как клиент сервиса
    Я хочу получать 400 на тело другого вида, и хранилище при этом не меняется

    Scenario Outline: невалидное тело отклоняется
        When клиент отправляет POST /items <заголовок> и телом:
            """
            <тело>
            """
        Then код ответа 400
        And ответ имеет Content-Type application/json
        And тело ответа — JSON-объект
        And хранилище записей не изменилось

        Examples:
            | набор           | заголовок                                        | тело                     |
            | missing-value   | с Content-Type application/json                  | {}                       |
            | extra-key       | с Content-Type application/json                  | {"value": 1, "extra": 2} |
            | null-value      | с Content-Type application/json                  | {"value": null}          |
            | array-value     | с Content-Type application/json                  | {"value": [1]}           |
            | object-value    | с Content-Type application/json                  | {"value": {"a": 1}}      |
            | invalid-json    | с Content-Type application/json                  | {"value":                |
            | form-body       | с Content-Type application/x-www-form-urlencoded | value=foo                |
            | x-json-body     | с Content-Type application/x-json                | {"value": 1}             |
            | overflow-float  | с Content-Type application/json                  | {"value": 1e400}         |
            | no-content-type | без Content-Type                                 | {"value": 1}             |
