<?php

declare(strict_types=1);

namespace Drupal\marvin\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Annotated command input validator.
 *
 * ```php
 * // Example usage.
 * #[ValidateExplode(
 *   type: 'option',
 *   name: 'foo',
 *   config: [
 *     'delimiter' => ',',
 *   ],
 * )]
 * ```
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ValidateExplode {

  public const AC_SELECTOR = 'marvinValidateExplode';

  /**
   * @phpstan-param array{string, mixed} $config
   *
   * @todo Phpstan type.
   */
  public function __construct(
    protected string $type,
    protected string $name,
    protected array $config = [
      'delimiter' => ',',
    ],
  ) {
    $this->config += [
      'delimiter' => ',',
    ];
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
        'config' => $args['config'] ?? $args[2] ?? [],
      ]),
    );
  }

}
