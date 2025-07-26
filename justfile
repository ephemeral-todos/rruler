default:
    just --list

[doc('Sets up project')]
build: vendor

[doc('Sets up phive')]
phive:
    phive install

[doc('Sets up developer tooling')]
tools: phive

[doc('Sets up vendor directory')]
vendor: tools
    composer install

[group('phpunit')]
[doc('Runs all tests')]
phpunit: vendor
    composer test

[group('phpunit')]
[doc('Runs unit tests')]
phpunit-unit: vendor
    composer test:unit

[group('phpunit')]
[doc('Runs integration tests')]
phpunit-integration: vendor
    composer test:integration

