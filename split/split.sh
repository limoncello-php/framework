#!/bin/bash

git subsplit init git@github.com:limoncello-php/framework.git && \
git subsplit publish --heads="master" --no-tags components/OAuthServer/:git@github.com:limoncello-php-dist/oauth-server.git &&\
git subsplit publish --heads="master" --no-tags components/Contracts/:git@github.com:limoncello-php-dist/contracts.git &&\
rm -rf .subsplit/
