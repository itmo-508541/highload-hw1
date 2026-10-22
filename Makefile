.DEFAULT_GOAL := init

.PHONY: init up deps owner test-prepare test test-env behat cs phpstan rector qa

init: up
	docker compose exec php phing

up:
	docker compose up -d

deps:
	docker compose exec php composer install

# Phing -Dapp.env=test готовит dotenv для runner'а (как в CI).
test-prepare:
	docker compose exec -T php phing -Dapp.env=test

# Optional: ARGS="path/to/Test.php" (avoids GNU make treating extra CLI words as extra targets)
# Suite Unit окружения не требует: цель ничего не готовит.
test:
	docker compose exec -T php vendor/bin/phpunit --testsuite=Unit $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

# Suite'ам Integration и E2E нужны ядро, Redis и браузер.
test-env: test-prepare
	docker compose exec -T php vendor/bin/phpunit --testsuite=Integration,E2E $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

# Optional: ARGS="path/to/feature.feature" (avoids GNU make treating extra CLI words as extra targets)
# -T: без псевдо-TTY (CI/агенты), без интерактивных ожиданий со стороны Docker
# --no-interaction: Behat не задаёт вопросы в терминал (сниппеты и т.п.)
# Suite один; браузерный срез режется тегом @javascript, приложение под Panther — APP_ENV=e2e.
behat: test-prepare
	docker compose exec -T php vendor/bin/behat --no-interaction $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

cs:
	docker compose exec php vendor/bin/php-cs-fixer fix

phpstan:
	docker compose exec php vendor/bin/phpstan analyze

rector:
	docker compose exec php vendor/bin/rector

qa: up cs phpstan rector test test-env behat

# Extra paths after `make test` are forwarded to PHPUnit (GNU make also lists them as targets; harmless).
# Alternatively: make test ARGS="tests/Unit/EventListener/CorsRequestListenerTest.php"
# Extra paths after `make behat` are forwarded to Behat (GNU make also lists them as targets; harmless).
# Alternatively: make behat ARGS="features/admin_access_login.feature"
%:
	@:
