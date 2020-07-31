[![Project Management](https://img.shields.io/badge/project-management-blue.svg)](https://waffle.io/lolltec/limoncello-php-framework)
[![License](https://img.shields.io/github/license/lolltec/limoncello-php-framework.svg)](https://packagist.org/packages/lolltec/limoncello-php-framework)

## Testing

```
composer test
```

The command above will run

- Code coverage tests for all components (`phpunit`) except `Contracts`.
- Code style checks for for all components (`phpcs`).
- Code checks for all components (`phpmd`).

Requirements

- 100% test coverage.
- zero issues from both `phpcs` and `phpmd`.

### Component Status

| Component          | Build Status  | Test Coverage  |
| -------------------|:-------------:| :-------------:|
| Application        | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-application.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-application) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-application/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-application/?branch=master) |
| Auth               | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-auth.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-auth) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-auth/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-auth/?branch=master) |
| Commands           | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-commands.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-commands) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-commands/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-commands/?branch=master) |
| Container          | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-container.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-container) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-container/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-container/?branch=master) |
| Core               | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-core.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-core) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-core/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-core/?branch=master) |
| Crypt              | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-crypt.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-crypt) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-crypt/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-crypt/?branch=master) |
| Data               | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-data.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-data) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-data/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-data/?branch=master) |
| Events             | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-events.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-events) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-events/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-events/?branch=master) |
| Flute              | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-flute.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-flute) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-flute/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-flute/?branch=master) |
| L10n               | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-l10n.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-l10n) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-l10n/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-l10n/?branch=master) |
| OAuthServer        | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-oauth-server.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-oauth-server) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-oauth-server/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-oauth-server/?branch=master) |
| Passport           | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-passport.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-passport) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-passport/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-passport/?branch=master) |
| Redis Tagged Cache | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-redis-tagged-cache.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-redis-tagged-cache) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-redis-tagged-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-redis-tagged-cache/?branch=master) |
| Templates          | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-templates.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-templates) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-templates/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-templates/?branch=master) |
| Testing            | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-testing.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-testing) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-testing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-testing/?branch=master) |
| Validation         | [![Build Status](https://travis-ci.org/lolltec/limoncello-php-component-validation.svg?branch=master)](https://travis-ci.org/lolltec/limoncello-php-component-validation) | [![Code Coverage](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-validation/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lolltec/limoncello-php-component-validation/?branch=master) |
