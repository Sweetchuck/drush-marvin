<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\Attributes\ValidateArrayLength;
use Drupal\marvin\Attributes\ValidateExplode;
use Drupal\marvin\Attributes\ValidateRuntimeEnvironmentId;
use Drush\Attributes as CLI;

class MarvinCommands extends CommandsBase {

  #[CLI\Hook(
    type: HookManager::ARGUMENT_VALIDATOR,
    selector: ValidateArrayLength::AC_SELECTOR,
  )]
  public function onHookValidateMarvinArrayLength(CommandData $commandData): ?CommandError {
    $annotationKey = ValidateArrayLength::AC_SELECTOR;
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $args = json_decode($annotationData->get($annotationKey));
    $inputType = $args['type'];
    $inputName = $args['name'];
    $constraint = $args['constraint'];

    $value = $inputType === 'option' ?
      $commandData->input()->getOption($inputName)
      : $commandData->input()->getArgument($inputName);

    $errorMsgPrefix = sprintf(
      "%s %s",
      $inputType,
      $inputType === 'option' ? "--$inputName" : $inputName,
    );
    $numOfItems = count($value);
    if ($constraint['required'] && !$numOfItems) {
      return new CommandError(
        sprintf("%s: is required.", $errorMsgPrefix),
        1,
      );
    }

    if ($constraint['min'] !== NULL && $numOfItems < $constraint['min']) {
      return new CommandError(
        sprintf(
          "%s: Minimum number of items: %d; Current number of items: %d",
          $errorMsgPrefix,
          $constraint['min'],
          $numOfItems,
        ),
        1,
      );
    }

    if ($constraint['max'] !== NULL && $numOfItems > $constraint['max']) {
      return new CommandError(
        sprintf(
          "%s: Maximum number of items: %d; Current number of items: %d",
          $errorMsgPrefix,
          $constraint['max'],
          $numOfItems,
        ),
        1,
      );
    }

    return NULL;
  }

  /**
   * Explodes string items in the input array.
   *
   * #[ValidateExplode(
   *   type: 'option',
   *   name: 'bar',
   * )]
   * public function foo(array $options = ['bar' => []]) {}
   * Example command: `drush foo --bar='a' --bar='b,c' --bar='d'`
   * Original would be: $options['bar'] = ['a', 'b,c', 'd'];
   * Actual result: $options['bar'] = ['a', 'b', 'c', 'd'];
   */
  #[CLI\Hook(
    type: HookManager::ARGUMENT_VALIDATOR,
    selector: ValidateExplode::AC_SELECTOR,
  )]
  public function onHookValidateMarvinExplode(CommandData $commandData): ?CommandError {
    $annotationKey = ValidateExplode::AC_SELECTOR;
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $args = json_decode($annotationData->get($annotationKey));
    $values = $args['type'] === 'option' ?
      $commandData->input()->getOption($args['name'])
      : $commandData->input()->getArgument($args['name']);

    $pattern = '/\s*' . preg_quote($args['config']['delimiter']) . '\s*/';
    $result = [];
    foreach ($values as $value) {
      $result = array_merge(
        $result,
        array_filter(
          preg_split($pattern, trim($value)) ?: [],
          'mb_strlen',
        ),
      );
    }

    $args['type'] === 'option' ?
      $commandData->input()->setOption($args['name'], $result)
      : $commandData->input()->setArgument($args['name'], $result);

    return NULL;
  }

  #[CLI\Hook(
    type: HookManager::ARGUMENT_VALIDATOR,
    selector: ValidateRuntimeEnvironmentId::AC_SELECTOR,
  )]
  public function onHookValidateMarvinRuntimeEnvironmentId(CommandData $commandData): ?CommandError {
    $annotationKey = ValidateRuntimeEnvironmentId::AC_SELECTOR;
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $args = json_decode($annotationData->get($annotationKey), TRUE);
    $value = $args['type'] === 'options' ?
      $commandData->input()->getOption($args['name'])
      : $commandData->input()->getArgument($args['name']);

    return array_key_exists($value, $this->getRuntimeEnvironments()) ?
      NULL
      : new CommandError(sprintf(
        'Value "%s" provided for %s is not a valid runtime environment identifier. List valid values with the following Drush command: %s',
        $value,
        ($args['type'] === 'option' ? "option --{$args['name']}" : "argument '{$args['name']}'"),
        'marvin:runtime-environment:list',
      ));
  }

}
