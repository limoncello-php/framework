[![Project Management](https://img.shields.io/badge/project-management-blue.svg)](https://waffle.io/limoncello-php/framework)
[![License](https://img.shields.io/github/license/limoncello-php/framework.svg)](https://packagist.org/packages/limoncello-php/framework)

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
| Application        | [![Build Status](https://travis-ci.org/limoncello-php-dist/application.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/application) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/application/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/application/?branch=master) |
| Auth               | [![Build Status](https://travis-ci.org/limoncello-php-dist/auth.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/auth) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/auth/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/auth/?branch=master) |
| Commands           | [![Build Status](https://travis-ci.org/limoncello-php-dist/commands.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/commands) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/commands/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/commands/?branch=master) |
| Container          | [![Build Status](https://travis-ci.org/limoncello-php-dist/container.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/container) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/container/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/container/?branch=master) |
| Core               | [![Build Status](https://travis-ci.org/limoncello-php-dist/core.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/core) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/core/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/core/?branch=master) |
| Crypt              | [![Build Status](https://travis-ci.org/limoncello-php-dist/crypt.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/crypt) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/crypt/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/crypt/?branch=master) |
| Data               | [![Build Status](https://travis-ci.org/limoncello-php-dist/data.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/data) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/data/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/data/?branch=master) |
| Events             | [![Build Status](https://travis-ci.org/limoncello-php-dist/events.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/events) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/events/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/events/?branch=master) |
| Flute              | [![Build Status](https://travis-ci.org/limoncello-php-dist/flute.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/flute) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/flute/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/flute/?branch=master) |
| L10n               | [![Build Status](https://travis-ci.org/limoncello-php-dist/l10n.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/l10n) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/l10n/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/l10n/?branch=master) |
| OAuthServer        | [![Build Status](https://travis-ci.org/limoncello-php-dist/oauth-server.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/oauth-server) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/oauth-server/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/oauth-server/?branch=master) |
| Passport           | [![Build Status](https://travis-ci.org/limoncello-php-dist/passport.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/passport) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/passport/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/passport/?branch=master) |
| Redis Tagged Cache | [![Build Status](https://travis-ci.org/limoncello-php-dist/redis-tagged-cache.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/redis-tagged-cache) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/redis-tagged-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/redis-tagged-cache/?branch=master) |
| Templates          | [![Build Status](https://travis-ci.org/limoncello-php-dist/templates.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/templates) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/templates/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/templates/?branch=master) |
| Testing            | [![Build Status](https://travis-ci.org/limoncello-php-dist/testing.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/testing) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/testing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/testing/?branch=master) |
| Validation         | [![Build Status](https://travis-ci.org/limoncello-php-dist/validation.svg?branch=master)](https://travis-ci.org/limoncello-php-dist/validation) | [![Code Coverage](https://scrutinizer-ci.com/g/limoncello-php-dist/validation/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/limoncello-php-dist/validation/?branch=master) |
