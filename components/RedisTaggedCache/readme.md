[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-redis-tagged-cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-redis-tagged-cache/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-redis-tagged-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-redis-tagged-cache/?branch=master)
[![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-redis-tagged-cache.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-redis-tagged-cache)
[![License](https://img.shields.io/github/license/lolltec/limoncello-php-framework.svg)](https://packagist.org/packages/lolltec/limoncello-php-framework)

## Summary

This is component for [Limoncello Framework](https://github.com/lolltec/limoncello-php-framework) that adds storing extra information (tags) in [Redis](https://redis.io/) cache.

Each method works in transactional way. The methods implement

- Adding value to cache with associated string tags and TTL (time-to-live).
- Removing cached value by key and associated tags.
- Invalidation (deletion) all cached values by tag.

It is designed to be easily combined with classes that work with Redis cache. All methods could be renamed using [PHP Trait Conflict Resolution](https://www.php.net/manual/en/language.oop5.traits.php#language.oop5.traits.conflict) and visibility changed with [PHP Trait Changing Method Visibility](https://www.php.net/manual/en/language.oop5.traits.php#language.oop5.traits.visibility).

Integration example

```php
use Limoncello\RedisTaggedCache\RedisTaggedCacheTrait;
use Redis;

/** @var Redis $redis */
$redis = ...;

$cache = new class ($redis)
{
    use RedisTaggedCacheTrait;

    public function __construct(Redis $redis)
    {
        $this->setRedisInstance($redis);
    }
};

$cache->addTaggedValue('key1', 'value1', ['author:1', 'comment:2']);
$cache->addTaggedValue('key2', 'value2', ['author:1', 'comment:2']);
$cache->addTaggedValue('key3', 'value3', ['author:1', 'comment:2']);

// removes the first key-pair
$cache->removeTaggedValue('key1');

// removes 2 remaining values
$cache->invalidateTag('author:1');
```

[More info](https://github.com/lolltec/limoncello-php-framework).

## Testing

```bash
$ composer test
```
