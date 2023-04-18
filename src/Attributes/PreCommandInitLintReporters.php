<?php

declare(strict_types=1);

namespace Drupal\marvin\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Drush\Commands\marvin\LintHooksCommands;

/**
 * Drush command annotator.
 *
 * ```php
 * use Drupal\marvin\Attributes as MarvinCLI;
 *
 * #[MarvinCLI\PreCommandInitLintReporters()]
 * ```
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class PreCommandInitLintReporters {

  /**
   * @phpstan-param \ReflectionAttribute<\Robo\Tasks> $attribute
   */
  public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo): void {
    $commandInfo->addAnnotation(
      LintHooksCommands::TAG_PRE_COMMAND_MARVIN_INIT_LINT_REPORTERS,
      [],
    );
  }

}
