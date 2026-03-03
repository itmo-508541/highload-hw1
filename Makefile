.DEFAULT_GOAL := init

.PHONY: init deps test cs phpstan rector

init:
	docker compose exec php phing

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
