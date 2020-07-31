#!/usr/bin/env bash

BRANCHES='master upstream develop'

COMPONENTS=(
    'components/Application/:git@github.com:lolltec/limoncello-php-component-application.git'
    'components/Auth/:git@github.com:lolltec/limoncello-php-component-auth.git'
    'components/Commands/:git@github.com:lolltec/limoncello-php-component-commands.git'
    'components/Common/:git@github.com:lolltec/limoncello-php-component-common.git'
    'components/Container/:git@github.com:lolltec/limoncello-php-component-container.git'
    'components/Contracts/:git@github.com:lolltec/limoncello-php-component-contracts.git'
    'components/Core/:git@github.com:lolltec/limoncello-php-component-core.git'
    'components/Crypt/:git@github.com:lolltec/limoncello-php-component-crypt.git'
    'components/Data/:git@github.com:lolltec/limoncello-php-component-data.git'
    'components/Events/:git@github.com:lolltec/limoncello-php-component-events.git'
    'components/Flute/:git@github.com:lolltec/limoncello-php-component-flute.git'
    'components/L10n/:git@github.com:lolltec/limoncello-php-component-l10n.git'
    'components/OAuthServer/:git@github.com:lolltec/limoncello-php-component-oauth-server.git'
    'components/Passport/:git@github.com:lolltec/limoncello-php-component-passport.git'
    'components/RedisTaggedCache/:git@github.com:lolltec/limoncello-php-component-redis-tagged-cache.git'
    'components/Templates/:git@github.com:lolltec/limoncello-php-component-templates.git'
    'components/Testing/:git@github.com:lolltec/limoncello-php-component-testing.git'
    'components/Validation/:git@github.com:lolltec/limoncello-php-component-validation.git'
)
