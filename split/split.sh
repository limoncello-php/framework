#!/bin/bash

git subsplit init git@github.com:limoncello-php/framework.git && \
git subsplit publish --heads="master" --no-tags components/Container/:git@github.com:limoncello-php-dist/container.git &&\
git subsplit publish --heads="master" --no-tags components/Contracts/:git@github.com:limoncello-php-dist/contracts.git &&\
git subsplit publish --heads="master" --no-tags components/OAuthServer/:git@github.com:limoncello-php-dist/oauth-server.git &&\
git subsplit publish --heads="master" --no-tags components/Passport/:git@github.com:limoncello-php-dist/passport.git &&\
rm -rf .subsplit/
