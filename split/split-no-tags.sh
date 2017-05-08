#!/usr/bin/env bash

CURRENT_DIR="$(dirname "$0")"
TMP_DIR="$(mktemp --tmpdir=/dev/shm/ -d)"
THREAD_ID=0

source "${CURRENT_DIR}/components.sh"

rm -rf .subsplit/ && git subsplit init git@github.com:limoncello-php/framework.git

for component in "${COMPONENTS[@]}"
do
  ((THREAD_ID++))
  THREAD_FOLDER=${TMP_DIR}/${THREAD_ID}
  (\
    mkdir ${THREAD_FOLDER} &&\
    cp -r ${CURRENT_DIR}/.subsplit/ ${THREAD_FOLDER} &&\
    cd ${THREAD_FOLDER} &&\
    git subsplit publish --heads="${BRANCHES}" --no-tags "${component}" -q \
  ) &
done

for job in `jobs -p`
do
  wait $job
done

rm -rf .subsplit/ && rm -rf ${TMP_DIR}
