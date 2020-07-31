[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-validation/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-validation/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-validation/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-validation/?branch=master)
[![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-validation.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-validation)
[![License](https://img.shields.io/packagist/l/lolltec/limoncello-php-validation.svg)](https://packagist.org/packages/lolltec/limoncello-php-validation)

This validation library fast, easy to use yet very powerful and flexible solution. Unlike many other libraries it does not try to give you 'validation rules' for all possible cases because those implementations might not fit your requirements and using such libraries is a pain. Instead it provides an extremely simple way of adding custom validation rules.

Also it supports caching of validation rules which makes it very fast. Custom error codes and messages are supported as well. Error messages could be customized/localized and support `placeholders`.

Usage sample

```php
$validator = v::validator([
    'sku'           => r::required(r::sku()),
    'amount'        => r::required(r::amount(5)),
    'delivery_date' => r::nullable(r::deliveryDate()),
    'email'         => r::email(),
    'address1'      => r::required(r::address1()),
    'address2'      => r::address2(),
    'accepted'      => r::required(r::areTermsAccepted()),
]);

$input = [
    'sku'    => '...',
    'amount' => '...',
    ...
];

if ($validator->validate($input)) {
    // use validated/converted/sanitized inputs
    $validated = $validator->getCaptures();
} else {
    // print validation errors
    $errors = $validator->getErrors();
}
```

Full sample code [is here](/sample).

As you can see such custom rules as `sku`, `amount`, `deliveryDate`, `address1`, `address2` and `areTermsAccepted` could be perfectly combined with built-in `required` and `nullable`. It makes the rules reusable in `CREATE` and `UPDATE` operations where typically inputs are required on creation and optional on update.

How easy to write those rules? Many could be made from built-in ones below (e.g. `amount`, `address1`, `address2` and `areTermsAccepted`)

> `equals`, `notEquals`, `inValues`, `lessThan`, `lessOrEquals`, `moreThan`, `moreOrEquals`, `between`, `stringLengthBetween`, `stringLengthMin`, `stringLengthMax`, `regexp`, `nullable`, `stringToBool`, `stringToDateTime`, `stringToFloat`, `stringToInt`, `stringArrayToIntArray`, `andX`, `orX`, `ifX`, `success`, `fail`, `required`, `enum`, `filter`, `isArray`, `isString`, `isBool`, `isInt`, `isFloat`, `isNumeric`, `isDateTime`

```php
class Rules extends \Limoncello\Validation\Rules
{
    public static function sku(): RuleInterface
    {
        return static::stringToInt(new IsSkuRule());
    }

    public static function amount(int $max): RuleInterface
    {
        return static::stringToInt(static::between(1, $max));
    }

    public static function deliveryDate(): RuleInterface
    {
        return static::stringToDateTime(DateTime::ISO8601, new IsDeliveryDateRule());
    }

    public static function email(): RuleInterface
    {
        return static::isString(
            static::filter(FILTER_VALIDATE_EMAIL, null, Errors::IS_EMAIL, static::stringLengthMax(255))
        );
    }

    public static function address1(): RuleInterface
    {
        return static::isString(static::stringLengthBetween(1, 255));
    }

    public static function address2(): RuleInterface
    {
        return static::nullable(static::isString(static::stringLengthMax(255)));
    }

    public static function areTermsAccepted(): RuleInterface
    {
        return static::stringToBool(static::equals(true));
    }
}
```

Custom rule such as `IsSkuRule` might require quering database and could be added with minimal overhead

```php
class IsSkuRule extends ExecuteRule
{
    public static function execute($value, ContextInterface $context): array
    {
        $pdo   = $context->getContainer()->get(PDO::class);
        $isSku = ...;

        return $isSku === true ?
            self::createSuccessReply($value) :
            self::createErrorReply($context, $value, Errors::IS_VALID_SKU);
    }
}
```

When validator is created a developer can pass [PSR Container](http://www.php-fig.org/psr/psr-11/) with custom services and have access to this container from validation rules. Thus validation could be easily integrated with application logic.

**[Sample application](/sample)**

#### Installation

```bash
$ composer require lolltec/limoncello-php-validation
```

> Note: for message translation PHP-intl is needed.

#### Issues

Any related issues please send to [limoncello](https://github.com/lolltec/limoncello-php-framework).

#### Testing

```bash
$ composer test
```
