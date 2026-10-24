@domain:item_storage
Feature: Невалидное тело записи
    Чтобы в хранилище попадали только скалярные значения
    Как клиент сервиса
    Я хочу получать 400 на тело другого вида, и хранилище при этом не меняется

    Scenario Outline: невалидное тело отклоняется
        When клиент отправляет POST /items с телом:
            """
            <тело>
            """
        Then код ответа 400
        And хранилище записей не изменилось

        Examples:
            | набор         | тело                     |
            | missing-value | {}                       |
            | extra-key     | {"value": 1, "extra": 2} |
            | null-value    | {"value": null}          |
            | array-value   | {"value": [1]}           |
            | object-value  | {"value": {"a": 1}}      |
            | invalid-json  | {"value":                |
