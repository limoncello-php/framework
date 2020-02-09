Files for building dev-ops environment to check tests on platforms such as PHP 7.1, PHP 7.2 and PHP 7.3.

You need to have [composer](https://getcomposer.org/) and [docker compose](https://docs.docker.com/compose/overview/) installed on your machine and path to them should be added to you environment so they could be run by

```bash
$ composer
$ docker-compose
```

When you have `docker-compose` installed on your machine you can execute in each component's root (`./components/<Component Name>`) the following commands

```bash
$ composer test-unit-php-7-3
$ composer test-unit-php-7-4
```

First run will download docker image and install necessary PHP extensions (it will take some time and produce rather scary output) however further runs will be very fast and produce something like

```
> docker-compose run --rm cli_7_3_php php ./vendor/bin/phpunit
PHPUnit 7.5.1 by Sebastian Bergmann and contributors.

Runtime:       PHP 7.3.0
Configuration: /app/phpunit.xml

........................................                          40 / 40 (100%)

Time: 43 ms, Memory: 6.00MB

OK (40 tests, 182 assertions)
```
