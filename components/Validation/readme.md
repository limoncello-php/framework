[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/limoncello-php-dist/validation/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/validation/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/validation/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/validation/?branch=master)
[![Build Status](https://travis-ci.org/limoncello-php-dist/validation.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/validation)
[![License](https://img.shields.io/github/license/limoncello-php/framework.svg)](https://packagist.org/packages/limoncello-php/framework)

This validation library is simple to use yet very powerful and flexible solution. Unlike many other libraries it do not try to give you 'validation rules' for all possible cases because in some cases these rules do not fit our requirements and using such libraries is a pain.

Would it be great if we could have reusable rules specific for our needs that are very easy to make and use?

```php
$rules = [
    'email'        => v::isEmail(),
    'first_name'   => v::isRequiredString(255),
    'last_name'    => v::isNullOrNonEmptyString(255),
    'payment_plan' => v::isExistingPaymentPlan(),
    'interests'    => v::isListOfStrings(),
];

// Validation rules for input are
// - `email` must be a string and a valid email value (as FILTER_VALIDATE_EMAIL describes)
// - `first_name` required in input, must be a string with length from 1 to 255
// - `last_name` could be either `null` or if given it must be a string with length from 1 to 255
// - `payment_plan` must be a valid index for data in database (we will emulate request to database)
// - `interests` must be an array of non-empty strings (any number of items, no limit for max length)
//
// Having app specific rules separated makes the code easier to read and reuse.
// Though you can have the rules in-lined.

$invalidInput = [
    'email'        => 'john.dow',              // not email
    //'first_name' => 'John',                  // field absent
    'last_name'    => '',                      // too short
    'payment_plan' => 123,                     // not existing ID in database 
    'interests'    => ['leisure', 'php', 321], // the last one is not string
];

$this->printErrors(
    v::validator(v::arrayX($rules))->validate($invalidInput)
);
```

And how difficult to 'develop' such rules? Less than a few lines of code or even just 1 line of code. [Check it out](/sample/Validation/Validator.php)

Error messages could be customized/localized and support `placeholders`. For sample above it would be

```
The `Email address` value should be a valid email address.
The `Last Name` value should be between 1 and 255 characters.
The `Payment plan` value should be an existing payment plan.
The `Interests` value should be a string.
The `First Name` value is required.
```

Note that error message placeholders like `first_name` are replaced with more readable such as `First Name` and others.

#### Installation

```bash
$ composer require limoncello-php/validation
```

> Note: for message translation PHP-intl is needed.

> Install it with `apt-get install php-intl` or what is appropriate for your system. 

#### Issues

Any related issues please send to [limoncello](https://github.com/limoncello-php/framework).

#### Usage

```php
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\I18n\Locales\EnUsLocale;
use Limoncello\Validation\I18n\Translator;
use Limoncello\Validation\Validator as v;

$rule = v::isDate(DateTime::RFC850)->setParameterName('your-date');

// The date is given in invalid format
foreach(v::validator($rule)->validate('2000-01-02') as $error) {
    /** @var Error $error */
    //...
}

// it will have 1 error with the following properties
$error->getParameterName();  // 'your-date'
$error->getParameterValue(); // '2000-01-02' 
$error->getMessageCode();    // MessageCodes::IS_DATE_TIME

$translator = new Translator(EnUsLocale::getLocaleCode(), EnUsLocale::getMessages());

// 'The `your-date` value should be a date in format `l, d-M-y H:i:s T`.'
$msg = $translator->translate($error); 
```

##### Supported rules

###### Basic validation rules

* required()
* isNull()
* notNull()
* regExp(string: $pattern)
* between(int: $min = null, int: $max = null)
* stringLength(int: $min = null, int: $max = null)
* isString()
* isBool()
* isInt()
* isFloat()
* isNumeric()
* isDate(string: $format)
* isArray()
* inValues(array $values, bool: $isStrict = true)

###### Advanced validation rules

* ifX(callable $condition, RuleInterface $onTrue, RuleInterface $onFalse) implements `IF`
* andX(RuleInterface $first, RuleInterface $second) - joins rules with `AND`
* orX(RuleInterface $first, RuleInterface $second) - joins rules with `OR`
* fail(int: $messageCode) - stub for `ifX`, `orX`, etc (add error)
* success() - stub for `ifX`, `orX`, etc (do not add any errors)
* objectX(array $rules) - validates data in object
* arrayX(array $rules) - validates data in associative array (named keys)
* eachX(RuleInterface $rule) - validates data in indexed array (numeric keys)
* callableX(callable $callable) - use any function in validation logic

Validation of **multidimensional** arrays is **supported**.

With these rules practically any validation logic could be added to the application. Check string to be either null or string with length from 1 to 255? Check row exists in database? Easy.

###### Captures

Valid data could be captured for further usage which is especially handy for deeply nested arrays/data structures. It will eliminate a need to parse such data twice. An example for validation and capturing of [JSON API](http://jsonapi.org/) attributes and relationship identities (both `to1` and `toMany`) could be found [here](/tests/CapturesTest.php). 

###### Complex rule for strings sample

```php
// identical rules, just different ways to get same result
$stringRule1 = v::ifX('is_null', v::success(), v::andX(v::isString(), v::stringLength(1, 255)));
$stringRule2 = v::orX(v::andX(v::isString(), v::stringLength(1, 255)), v::isNull());
```

###### Database existence rule sample

```php
$customErrorCode = 123456;
$checkDatabase   = function ($recordId) {
    // emulate database request
    $recordExists = $recordId < 10;
    return $recordExists;
};
$existsRule = v::callableX($checkDatabase, $customErrorCode);

// no error
$this->readErrors(v::validator($existsRule)->validate(5));

// has error
$this->readErrors(v::validator($existsRule)->validate(15));
```

###### Indexed array (numeric keys) data sample

```php
$input = [
    'key1' => ['field1', 123, 'field2'],
];

$rules = v::arrayX([
    'key1' => v::eachX(v::isString()),
]);

foreach (v::validator($rules)->validate($input) as $error) {
    // will return 1 error with
    // $error->getParameterName() === 'key1'
    // $error->getParameterValue() === 123
    // $error->getMessageCode() === MessageCodes::IS_STRING
    // $translator->translate($error) === 'The `key1` value should be a string.'
}
```

###### Nested data sample

```php
$input = [
    'key1' => [
        'key2' => 'field1',
        'key3' => 123,
        'key4' => 'field2'
    ],
];

$rules = v::arrayX([
    'key1'  => v::arrayX([
        'key2' => v::isString(),
        'key3' => v::isString(),
        'key4' => v::isString(),
    ]),
]);

foreach (v::validator($rules)->validate($input) as $error) {
    // will return 1 error with
    // $error->getParameterName() === 'key3'
    // $error->getParameterValue() === 123
    // $error->getMessageCode() === MessageCodes::IS_STRING
    // $translator->translate($error) === 'The `key3` value should be a string.'
}
```

#### Recommended usage

Inherit default validator and message translator. Add rules and error messages specific for your app. Also you can set up custom placeholders in error messages so you will get 'The `First Name` value is invalid.' rather than 'The `first_name` value is invalid.'

**[Sample application](/sample)**

#### Testing

```bash
$ composer test
```
