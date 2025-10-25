.PHONY: install test stan csfix lint qa

install:
	composer install

test:
	vendor/bin/phpunit -c phpunit.xml

stan:
	vendor/bin/phpstan analyse --no-progress --memory-limit=512M

csfix:
	vendor/bin/php-cs-fixer fix --diff --verbose --quiet

lint:
	vendor/bin/php-cs-fixer fix --diff --dry-run --verbose --quiet

qa: test stan lint

forge:
	bin/ddd-forge