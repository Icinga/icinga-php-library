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
PHP_VERSION=$(echo "<?= join('.', [PHP_MAJOR_VERSION, PHP_MINOR_VERSION]); ?>" | php 2>/dev/null)

if [[ -n $(git branch | grep $BRANCH) ]]; then
  git branch -D $BRANCH
fi

git checkout -b $BRANCH
git merge --no-ff -m "Merge latest tag, to make it reachable for git-describe" $LATEST_TAG

composer config platform.php $PHP_VERSION
composer require --no-update \
  php:$PHP_VERSION \
  ipl/html:"dev-master as 99.x-dev" \
  ipl/i18n:"dev-master as 99.x-dev" \
  ipl/orm:"dev-master as 99.x-dev" \
  ipl/sql:"dev-master as 99.x-dev" \
  ipl/stdlib:"dev-master as 99.x-dev" \
  ipl/validator:"dev-master as 99.x-dev" \
  ipl/web:"dev-master as 99.x-dev"

git commit -a -m "Require dev-master everywhere"
bin/make-release.sh "$NEXT_VERSION-dev" --no-checkout
