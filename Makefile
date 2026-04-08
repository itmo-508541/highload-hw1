.DEFAULT_GOAL := init

.PHONY: init up deps test cs phpstan rector qa

init: up
	docker compose exec php phing

up:
	docker compose up -d

deps:
	docker compose exec php composer install

test:
	docker compose exec php vendor/bin/phpunit

cs:
	docker compose exec php vendor/bin/php-cs-fixer fix

phpstan:
	docker compose exec php vendor/bin/phpstan analyze

rector:
	docker compose exec php vendor/bin/rector

qa: up cs phpstan rector test
