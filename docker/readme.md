Files for building dev-ops environment to check tests on platforms such as PHP 7.0 and PHP 7.1.

You need to have [composer](https://getcomposer.org/) and [docker compose](https://docs.docker.com/compose/overview/) installed on your machine and path to them should be added to you environment so they could be run by

```bash
$ composer
$ docker-compose
```

When you have `docker-compose` installed on your machine you can execute in each component's root (`./components/<Component Name>`) the following commands

```bash
$ composer test-unit-php-7-0
$ composer test-unit-php-7-1
```

First run will download docker image and install necessary PHP extensions (it will take some time and produce rather scary output) however further runs will be very fast and produce something like

```
> docker-compose run --rm cli_7_0_php php ./vendor/bin/phpunit
PHPUnit 5.7.16 by Sebastian Bergmann and contributors.

Runtime:       PHP 7.0.17
Configuration: /app/phpunit.xml

...............................................................  63 / 116 ( 54%)
.....................................................           116 / 116 (100%)

Time: 1.69 seconds, Memory: 16.00MB

OK (116 tests, 700 assertions)
```
