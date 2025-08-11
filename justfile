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

[group('phpunit')]
[doc('Runs sabre/dav incompatibility tests')]
phpunit-sabre-dav-incompatibility: vendor
    composer test:sabre-dav-incompatibility

[group('development')]
[doc('Runs all tests')]
test: vendor
    composer test

[group('development')]
[doc('Runs static analysis')]
analyse: vendor
    composer analyse

[group('development')]
[doc('Formats code')]
format: vendor
    composer format

[group('development')]
[doc('Checks code formatting')]
format-check: vendor
    composer format:check

[group('development')]
[doc('Runs all quality checks')]
check: vendor
    composer analyse
    composer format:check
    composer test

[group('development')]
[doc('Generates API documentation')]
docs: vendor
    composer docs

[group('development')]
[doc('Runs sabre/dav incompatibility tests')]
test-sabre-dav-incompatibility: vendor
    composer test:sabre-dav-incompatibility

