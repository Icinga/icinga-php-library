#!/bin/bash

set -xe

BRANCH="$1"

if [ -z $BRANCH ]; then
  echo "USAGE: $0 <branch>"
  echo " e.g.: $0 snapshot/nightly"
  exit 1
fi

LATEST_TAG=$(git for-each-ref refs/tags --sort=-taggerdate --format='%(refname)' --count=1 | awk -F/ '{print $3}')
TAGGED_BRANCH="stable/${LATEST_TAG:1}"
NEXT_VERSION=$(echo "${LATEST_TAG:1}" | awk -F. -v OFS=. '{$3=0}; {++$2}; {print}')

git branch -D $BRANCH
git checkout -b $BRANCH
git merge --no-ff -m "Merge latest stable, to make the latest tag reachable" $TAGGED_BRANCH

composer require --no-update \
  ipl/html:@dev \
  ipl/i18n:@dev \
  ipl/orm:@dev \
  ipl/sql:@dev \
  ipl/stdlib:@dev \
  ipl/validator:@dev \
  ipl/web:@dev

git commit -a -m "Require dev-master everywhere"
bin/make-release.sh "$NEXT_VERSION-dev" --no-checkout
