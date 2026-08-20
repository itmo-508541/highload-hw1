@e2e @javascript
Feature: Вход в Sonata Admin через форму логина
    Чтобы убедиться, что UserAuthentificatorAuthenticator пускает админов
    и access control не пускает обычных пользователей
    Как внешний клиент
    Я хочу войти через /admin/login в браузере

    Scenario: E2E-ADMIN-LOGIN-01 ROLE_ADMIN входит в Sonata Admin
        Given существует пользователь "e2e_admin" с паролем "AdminPass1!" и ролью "ROLE_ADMIN"
        When я открываю страницу входа в админку
        And я вхожу как "e2e_admin" с паролем "AdminPass1!"
        Then текущий URL должен содержать "/admin/dashboard"
        And я должен видеть админский интерфейс Sonata

    Scenario: E2E-ADMIN-LOGIN-02 ROLE_USER не получает доступ к Sonata Admin
        Given существует пользователь "e2e_user" с паролем "UserPass1!" и ролью "ROLE_USER"
        When я открываю страницу входа в админку
        And я вхожу как "e2e_user" с паролем "UserPass1!"
        Then я не должен иметь доступ к админскому интерфейсу Sonata
