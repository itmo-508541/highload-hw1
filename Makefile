.DEFAULT_GOAL := init

.PHONY: init up deps owner test test-e2e behat behat-e2e cs phpstan rector qa

init: up
	docker compose exec php phing

up:
	docker compose up -d

deps:
	docker compose exec php composer install

# Optional: ARGS="path/to/Test.php" (avoids GNU make treating extra CLI words as extra targets)
test:
	docker compose exec -T php vendor/bin/phpunit --testsuite=Unit $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

test-e2e:
	docker compose exec -T php vendor/bin/phpunit --testsuite=E2E $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

# Optional: ARGS="path/to/feature.feature" (avoids GNU make treating extra CLI words as extra targets)
# -T: без псевдо-TTY (CI/агенты), без интерактивных ожиданий со стороны Docker
# --no-interaction: Behat не задаёт вопросы в терминал (сниппеты и т.п.)
# Phing -Dapp.env=test готовит dotenv для runner'а (как в CI); приложение под Panther — APP_ENV=e2e.
behat:
	docker compose exec -T php phing -Dapp.env=test
	docker compose exec -T php vendor/bin/behat --suite=default --no-interaction $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

behat-e2e:
	docker compose exec -T php phing -Dapp.env=test
	docker compose exec -T php vendor/bin/behat --suite=e2e --no-interaction $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

cs:
	docker compose exec php vendor/bin/php-cs-fixer fix

phpstan:
	docker compose exec php vendor/bin/phpstan analyze

rector:
	docker compose exec php vendor/bin/rector

qa: up cs phpstan rector test behat test-e2e behat-e2e

# Extra paths after `make test` are forwarded to PHPUnit (GNU make also lists them as targets; harmless).
# Alternatively: make test ARGS="tests/EventListener/CorsRequestListenerTest.php"
# Extra paths after `make behat` are forwarded to Behat (GNU make also lists them as targets; harmless).
# Alternatively: make behat ARGS="features/phing_dotenv_property_task.feature"
%:
	@:
