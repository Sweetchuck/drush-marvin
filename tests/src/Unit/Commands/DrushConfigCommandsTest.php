<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Consolidation\Config\Config;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use Drush\Commands\marvin\DrushConfigCommands;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\DrushConfigCommands
 * @covers \Drush\Commands\marvin\CommandsBase
 */
class DrushConfigCommandsTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesDrushConfig(): array {
    return [
      'empty' => [
        [
          'drush' => [
            'vendor-dir' => '.',
          ],
          'options' => [
            'decorated' => FALSE,
            'interactive' => TRUE,
          ],
        ],
        [],
        [],
      ],
    ];
  }

  /**
   * @dataProvider casesDrushConfig
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $configData
   * @phpstan-param array<string, mixed> $options
   */
  public function testDrushConfig(array $expected, array $configData, array $options): void {
    $this->config->addContext('foo', new Config($configData));

    $commands = new DrushConfigCommands();
    $commands->setConfig($this->config);

    $actual = $commands->cmdDrushConfigExecute($options);
    static::assertSame(
      $expected,
      $actual->getOutputData(),
    );
  }

}
