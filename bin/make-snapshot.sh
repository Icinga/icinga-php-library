#!/bin/bash

set -xe

BRANCH="$1"

if [ -z $BRANCH ]; then
  echo "USAGE: $0 <branch>"
  echo " e.g.: $0 snapshot/nightly"
  exit 1
fi

LATEST_TAG=$(git for-each-ref refs/tags --sort=-taggerdate --format='%(refname)' --count=1 | awk -F/ '{print $3}')
NEXT_VERSION=$(echo "${LATEST_TAG:1}" | awk -F. -v OFS=. '{$3=0}; {++$2}; {print}')

if [[ -n $(git branch | grep $BRANCH) ]]; then
  git branch -D $BRANCH
fi

git checkout -b $BRANCH
git merge --no-ff -m "Merge latest tag, to make it reachable for git-describe" $LATEST_TAG

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
