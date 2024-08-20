<?php

declare(strict_types=1);

namespace Drupal\marvin\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Annotated command input validator.
 *
 * ```php
 * // Example usage.
 * #[ValidateArrayLength(
 *   type: 'option',
 *   name: 'foo',
 *   config: [
 *     'required' => TRUE,
 *     'min' => 2,
 *     'max' => 9,
 *   ],
 * )]
 * ```
 *
 * @see \Drush\Commands\marvin\MarvinCommands::onHookValidateMarvinArrayLength
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ValidateArrayLength {

  public const AC_SELECTOR = 'validate-marvin-array-length';

  /**
   * @phpstan-param array{required?: bool, min?: ?int<0, max>, max?: ?int<0, max>} $config
   *
   * @todo Phpstan type.
   */
  public function __construct(
    protected string $type,
    protected string $name,
    protected array $config = [
      'required' => FALSE,
      'min' => NULL,
      'max' => NULL,
    ],
  ) {
    $this->config += [
      'required' => FALSE,
      'min' => NULL,
      'max' => NULL,
    ];

    if ($this->config['required'] && $this->config['min'] === NULL) {
      $this->config['min'] = 1;
    }

    \assert(
      $this->config['min'] === NULL && $this->config['max'] === NULL,
      sprintf(
        '%s config is pointless without any limitation.',
        get_called_class(),
      ),
    );
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
        'config' => $args['config'] ?? $args[2],
      ]),
    );
  }

}
