
# Drupal - Marvin

[![CircleCI](https://circleci.com/gh/Sweetchuck/drupal-marvin/tree/2.x.svg?style=svg)](https://circleci.com/gh/Sweetchuck/drupal-marvin/?branch=2.x)
[![codecov](https://codecov.io/gh/Sweetchuck/drupal-marvin/branch/2.x/graph/badge.svg?token=hKwwzce33I)](https://app.codecov.io/gh/Sweetchuck/drupal-marvin/branch/2.x)


## @todo

## Consistent annotation tags.

**Goals**:
* Standard name format.
* Standard place.

**Occurrences**:
* \Drush\Commands\marvin\LintHooksCommands::TAG_PRE_COMMAND_MARVIN_INIT_LINT_REPORTERS
* \Drupal\marvin\Attributes\ValidateArrayLength::AC_SELECTOR
* \Drupal\marvin\Attributes\ValidateRuntimeEnvironmentId::AC_SELECTOR


### Update drush.yml on composer events

The task is to automatically update `./drush/drush.yml#drush.paths.config`
and `./drush/drush.yml/drush.paths.include` entries after `composer update`.


### Custom exported config checker

Error when any of the config refers to a `default` view_mode of an entity. \
The main point here is to always use one of the „dedicated” view_mode. \

* entity_reference field formatters
* Text format / CKEditor / Embed media config


### Miscellaneous

* https://packagist.org/packages/dealerdirect/phpcodesniffer-composer-installer
* Circular dependency
* Changelog generator
* *.service.yml Class exists checker
* Run Behat tests parallel
  * Duplicate a site (docroot/sites/*/)
    * HTTP server virtual hosts
    * file.public
    * file.private
    * file.temporary
    * database.default
    * Solr cores
    * Redis (prefix)
    * Memcached (prefix)
    * etc
  * Start CromeDriver/HeadlessCrome instances
  * Split Behat scenarios
