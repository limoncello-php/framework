#!/usr/bin/env bash

CURRENT_DIR="$(dirname "$0")"

source "${CURRENT_DIR}/components.sh"

git subsplit init git@github.com:limoncello-php/framework.git

for component in "${COMPONENTS[@]}"
do
  git subsplit publish --heads="${BRANCHES}" --no-tags "${component}"
done

rm -rf .subsplit/
