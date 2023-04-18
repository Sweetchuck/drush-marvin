<?php

declare(strict_types=1);

namespace Drupal\marvin\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Annotated command input validator.
 *
 * ```php
 * // Example usage.
 * #[ValidateRuntimeEnvironmentId(
 *   type: 'option',
 *   name: 'foo',
 * )]
 * ```
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ValidateRuntimeEnvironmentId {

  public const AC_SELECTOR = 'marvinValidateRuntimeEnvironmentId';

  public function __construct(
    protected string $type,
    protected string $name,
  ) {
  }

  /**
   * @phpstan-param \ReflectionAttribute<object> $attribute
   */
  public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo): void {
    $args = $attribute->getArguments();
    $commandInfo->addAnnotation(
      static::AC_SELECTOR,
      json_encode([
        'type' => $args['type'] ?? $args[0],
        'name' => $args['name'] ?? $args[1],
      ]),
    );
  }

}
