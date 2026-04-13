.DEFAULT_GOAL := init

.PHONY: init up deps test behat cs phpstan rector qa

init: up
	docker compose exec php phing

up:
	docker compose up -d

deps:
	docker compose exec php composer install

# Optional: ARGS="path/to/Test.php" (avoids GNU make treating extra CLI words as extra targets)
test:
	docker compose exec -T php vendor/bin/phpunit $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

# Optional: ARGS="path/to/feature.feature" (avoids GNU make treating extra CLI words as extra targets)
behat:
	docker compose exec php vendor/bin/behat $(if $(strip $(ARGS)),$(ARGS),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))

cs:
	docker compose exec php vendor/bin/php-cs-fixer fix

phpstan:
	docker compose exec php vendor/bin/phpstan analyze

rector:
	docker compose exec php vendor/bin/rector

qa: up cs phpstan rector test behat

# Extra paths after `make test` are forwarded to PHPUnit (GNU make also lists them as targets; harmless).
# Alternatively: make test ARGS="tests/EventListener/CorsRequestListenerTest.php"
# Extra paths after `make behat` are forwarded to Behat (GNU make also lists them as targets; harmless).
# Alternatively: make behat ARGS="features/demo.feature"
%:
	@:
