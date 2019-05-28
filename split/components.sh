#!/usr/bin/env bash

BRANCHES='master develop'

COMPONENTS=(
    'components/Application/:git@github.com:limoncello-php-dist/application.git'
    'components/Auth/:git@github.com:limoncello-php-dist/auth.git'
    'components/Commands/:git@github.com:limoncello-php-dist/commands.git'
    'components/Common/:git@github.com:limoncello-php-dist/common.git'
    'components/Container/:git@github.com:limoncello-php-dist/container.git'
    'components/Contracts/:git@github.com:limoncello-php-dist/contracts.git'
    'components/Core/:git@github.com:limoncello-php-dist/core.git'
    'components/Crypt/:git@github.com:limoncello-php-dist/crypt.git'
    'components/Data/:git@github.com:limoncello-php-dist/data.git'
    'components/Events/:git@github.com:limoncello-php-dist/events.git'
    'components/Flute/:git@github.com:limoncello-php-dist/flute.git'
    'components/L10n/:git@github.com:limoncello-php-dist/l10n.git'
    'components/OAuthServer/:git@github.com:limoncello-php-dist/oauth-server.git'
    'components/Passport/:git@github.com:limoncello-php-dist/passport.git'
    'components/RedisTaggedCache/:git@github.com:limoncello-php-dist/redis-tagged-cache.git'
    'components/Templates/:git@github.com:limoncello-php-dist/templates.git'
    'components/Testing/:git@github.com:limoncello-php-dist/testing.git'
    'components/Validation/:git@github.com:limoncello-php-dist/validation.git'
)
