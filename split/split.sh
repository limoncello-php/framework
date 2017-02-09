#!/bin/bash

git subsplit init git@github.com:limoncello-php/framework.git && \
git subsplit publish --heads="master" --no-tags components/OAuthServer/:git@github.com:limoncello-php-dist/oauth-server.git &&\
rm -rf .subsplit/
