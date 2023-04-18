#!/usr/bin/env bash

function app-git-config-init() {
  git config --global init.defaultBranch '1.x' || return 1

  git config --global user.name  'CircleCI' || return 2
  git config --global user.email 'circleci@circleci.com' || return 3
}

function app-version-ma0dmi0-to-ma2mi2() {
  local drupal_core_version_ma0dmi0="${1}"
    : "${drupal_core_version_ma0dmi0:?'argument is required'}"

  echo "${drupal_core_version_ma0dmi0}" \
  | \
  sed --regexp-extended --expression 's/\.([0-9])$/0\1/g'
}

function app-incubator-dir() {
  # @todo This argument is not used.
  local drupal_core_version_ma0dmi0="${1}"
    : "${drupal_core_version_ma0dmi0:?'argument is required'}"

  local workspace="${HOME}/project"

  local project_vendor
  project_vendor="$(id --name -u)"

  local project_name='incubator'

  echo "${workspace}/${project_vendor}/${project_name}"
}

function app-incubator-init() {
  local drupal_core_version_ma0dmi0="${1}"
  : "${drupal_core_version_ma0dmi0:?'argument is required'}"

  app-incubator-init-create-project "${drupal_core_version_ma0dmi0}" || return 1
  cd "$(app-incubator-dir "${drupal_core_version_ma0dmi0}")"         || return 2
  app-incubator-init-custom-files  || return 3
  app-incubator-init-git           || return 4
  app-incubator-init-composer-json || return 5
}

function app-incubator-init-create-project() {
  local drupal_core_version_ma0dmi0="${1}"
  : "${drupal_core_version_ma0dmi0:?'argument is required'}"

  local drupal_core_version_ma2mi2
  drupal_core_version_ma2mi2="$(app-version-ma0dmi0-to-ma2mi2 "${drupal_core_version_ma0dmi0}")"

  local incubator_dir
  incubator_dir="$(app-incubator-dir "${drupal_core_version_ma0dmi0}")"
  local incubator_dir_parent="${incubator_dir%/*}"
#  local incubator_dir_basename="${incubator_dir##*/}"

  local workspace="${HOME}/project"
  local project_vendor
  project_vendor="$(id --name -u)"
  local project_name="incubator-${drupal_core_version_ma2mi2}"

  mkdir --parents "${incubator_dir_parent}"
  composer create-project \
    --no-interaction \
    --remove-vcs \
    'drupal/recommended-project' \
    "${incubator_dir}" \
    "^${drupal_core_version_ma0dmi0}"
}

function app-incubator-init-custom-files() {
  cp \
    --recursive \
    --force \
    ~/project/drupal/marvin/.circleci/project/circleci/incubator/. \
    .

  local dir="${PWD##*/}"
  if [[ -d ~/project/drupal/marvin/.circleci/project/circleci/"${dir}" ]]; then
    cp \
      --recursive \
      --force \
      ~/project/drupal/marvin/.circleci/project/circleci/"${dir}"/. \
      .
  fi
}

function app-incubator-init-git() {
  git init  || return 1
  git add . || return 2
  git commit --message 'Initial commit' || return 3
}

function app-incubator-init-composer-json() {
  jq '.repositories = {}' composer.json > composer.json.tmp
  mv 'composer.json.tmp' 'composer.json'

  composer config 'minimum-stability'   'dev'
  composer config 'prefer-stable'       'true'
  composer config 'preferred-install.*' 'dist'

  composer config 'allow-plugins.cweagans/composer-patches'                      'true'
  composer config 'allow-plugins.dealerdirect/phpcodesniffer-composer-installer' 'true'

  composer config \
    'repositories.drupal' \
    'composer' \
    'https://packages.drupal.org/8' \
  || return 2

  composer config \
    'repositories.drupal/marvin' \
    'path' \
    '../../drupal/marvin' \
  || return 3

  composer config \
    'repositories.sweetchuck/drupal-drush-helper' \
    'github' \
    'https://github.com/Sweetchuck/drupal-drush-helper.git' \
  || return 4

  composer require \
    --no-interaction \
    'behat/mink' \
    'dealerdirect/phpcodesniffer-composer-installer' \
    'drupal/coder' \
    'drupal/marvin:*' \
    'drush/drush' \
    'mikey179/vfsstream' \
    'phpstan/phpstan' \
    'squizlabs/php_codesniffer' \
    'symfony/phpunit-bridge' \
    'phpunit/phpunit' \
    'weitzman/drupal-test-traits:2.x-dev'
}
