[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-l10n/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-l10n/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-l10n/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-l10n/?branch=master)
[![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-l10n.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-l10n)
[![License](https://img.shields.io/github/license/lolltec/limoncello-php-framework.svg)](https://packagist.org/packages/lolltec/limoncello-php-framework)

## Summary

This is localization component for [Limoncello Framework](https://github.com/lolltec/limoncello-php-framework).

The component helps to translate an application into different languages. In particular,  it provides an ability to manage localized files easily and extract localized strings for a given locale with a fallback to default one.

### Message Localization

For a given resource storage data (later on this) the usage looks like

```php
    $storage   = new BundleStorage($storageData);
    $localized = $storage->get('de_AT', 'ErrorMessages', 'id_or_message');
```

Where
- first parameter `de_AT` is a locale code.
- second parameter `ErrorMessages` is messages' namespace name. Using a namespace allows message isolation across namespaces. Typically namespace is a resource file name without `.php` file extension.
- third parameter `id_or_message` is a message identity. A message identity should be unique across a namespace.

It first finds the closest locale (`de_AT`, `de` or a default one such as `en` if no locale is found) and a corresponding message.

The result would contain the found message with their locale. For example

```php
    // $localized
    ['Hallo Welt', 'de'];
```

If resources for requested locale `de_AT` existed the result could look like
```php
    // $localized
    ['Hallo Welt aus Österreich', 'de_AT'];
```

### Resource Storage

In the example above resource storage data is an array that contains localized strings. The reason this object is introduced is a performance optimization. Looking for multiple files in a file system and analyzing their data is time and resource consuming. The storage data replaces many resource files with a single optimized for search array which could be cached.

For instance, there is a resource folder structure

```txt
Resources
    |_ de
        |_ Messages.php
    |_de_AT
        |_ Messages.php
```
Where `Messages.php` is a plain PHP array file

```php Resource/de/Messages.php
<?php
return [
    'Hello World' => 'Hallo Welt',
];
```
and

```php Resource/de_AT/Messages.php
<?php
return [
    'Hello World' => 'Hallo Welt aus Österreich',
];
```
Then, a storage array could be created as

```php
    $storageData = (new FileBundleEncoder('path/to/ResourcesFolder/'))->getStorageData('de');
```

Method `getStorageData` has a default locale code as a first parameter.

Finally, complete example
```php
    $storageData = (new FileBundleEncoder('path/to/ResourcesFolder/'))->getStorageData('de');
    $localized = (new BundleStorage($storageData))->get('de_AT', 'ErrorMessages', 'Hello World');
    // $localized
    ['Hallo Welt aus Österreich', 'de_AT'];
```

### Message Translation

In addition to Resource Storage, the package provides a helpful wrapper over [MessageFormatter](http://php.net/manual/en/class.messageformatter.php).

```php
    $storageData = (new FileBundleEncoder('path/to/ResourcesFolder/'))->getStorageData('en');
    $translator = new Translator(new BundleStorage($storageData));

    // 'Hallo Welt' (message is in the resources)
    $translator->translateMessage('de', 'Messages', 'Hello World');

    // 'Hallo Welt aus Österreich' (message is in the resources)
    $translator->translateMessage('de_AT', 'Messages', 'Hello World');

    // 'Good morning' (message not found in resources so it returns the key itself)
    $translator->translateMessage('de', 'Messages', 'Good morning');
```

Method `translateMessage` has signature `translateMessage(string $locale, string $namespace, string $key, array $args = []): string`. The method can accept formatting parameters via `$args` parameter. For advanced formatting samples see [MessageFormatter::formatMessage](http://php.net/manual/en/messageformatter.formatmessage.php).

There is another wrapper for `Translator` called `Formatter` which hides locale code and namespace. It could be used in an environment where locale for current session is defined for a logged in user. So the code basically asks for `FormatterInterface` for current locale and specified namespace.

```php
    $formatter = new Formatter('de', 'Messages', $translator);

    // 'Hallo Welt'
    $formatter->formatMessage('Hello World');

```

[More info](https://github.com/lolltec/limoncello-php-framework).

## Recommended Usage

- Have a resource folder with locales needed for your application.
- For the resource folder and default locale create a storage data with `FileBundleEncoder` and cache it.
- On page load read the storage data from the cache and load it into `Translator` with `BundleStorage`.
- Use the translator for message formatting.

## Testing

```bash
$ composer test
```
