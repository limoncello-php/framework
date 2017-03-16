Files for building dev-ops environment to check tests on platforms such as PHP 5.6, PHP 7.0 and HHVM.

You need to have [composer](https://getcomposer.org/) and [docker compose](https://docs.docker.com/compose/overview/) installed on your machine and path to them should be added to you environment so they could be run by

```bash
$ composer
$ docker-compose
```

So if you have docker compose installed on your machine you should go to project root dir (that's one level up from here) and you can run

```bash
$ composer test-php-7-0
$ composer test-php-5-6
$ composer test-hhvm
```

First run will download docker image and install necessary PHP extensions (it will produce rather scary output) but further runs will be very fast and produce something like

```
> docker-compose run --rm cli_5_6_php php ./vendor/bin/phpunit
PHPUnit 5.4.6 by Sebastian Bergmann and contributors.

Runtime:       PHP 5.6.23
Configuration: /app/phpunit.xml

....................................................              52 / 52 (100%)

Time: 5.09 seconds, Memory: 19.00MB

OK (52 tests, 346 assertions)
```
