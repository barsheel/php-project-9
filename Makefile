PORT ?= 8000

start:
	php -S 0.0.0.0:$(PORT) -t public public/index.php

init:
	composer init

require:
	composer require slim/slim slim/psr7 slim/http slim/php-view php-di/php-di
	composer require squizlabs/php_codesniffer
	composer require phpstan/phpstan
	composer require PHPUnit/PHPUnit

install:
	composer install
	composer validate

autoload:
	composer dump-autoload

lint:
	composer exec --verbose phpcbf -- --standard=PSR12 ./public ./src
	composer exec --verbose phpcs -- --standard=PSR12 ./public ./src

stan:
	composer exec phpstan -- analyze -c phpstan.neon

test:
	composer exec --verbose phpunit tests -- --display-warnings

test-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-text

test-coverage-html:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-html build/logs/coverage.html