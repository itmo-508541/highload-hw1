@domain:item_storage
Feature: Чтение записи
    Чтобы получить сохранённое значение
    Как клиент сервиса
    Я хочу прочитать запись по id и получить значение того же JSON-типа

    Scenario Outline: значение читается таким, каким записано
        Given существует запись со значением <значение>
        When клиент читает эту запись
        Then код ответа 200
        And ответ имеет Content-Type application/json
        And тело ответа — JSON:
            """
            {"value": <значение>}
            """

        Examples:
            | набор        | значение |
            | string-foo   | "foo"    |
            | string-digit | "1"      |
            | int          | 1        |
            | float        | 1.5      |
            | true         | true     |
            | false        | false    |
            | empty-string | ""       |

    Scenario Outline: запись не найдена
        When клиент отправляет GET <путь>
        Then код ответа 404
        And ответ имеет Content-Type application/json
        And тело ответа — JSON-объект

        Examples:
            | набор          | путь                        |
            | missing-id     | /items/999999999            |
            | non-numeric-id | /items/abc                  |
            | huge-id        | /items/99999999999999999999 |
