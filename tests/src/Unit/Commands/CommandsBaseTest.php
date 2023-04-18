<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drush\Commands\marvin\CommandsBase;
use League\Container\Container;
use Robo\Config\Config;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\CommandsBase
 */
class CommandsBaseTest extends CommandsTestBase {

  /**
   * @dataProvider casesGetEnvironment
   *
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetEnvironment(): array {
    return [
      'basic' => [
        'local',
      ],
      'override' => [
        'prod',
        [
          'marvin' => ['environment' => 'prod'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetEnvironment
   *
   * @phpstan-param array<string, mixed> $configData
   */
  public function testGetEnvironment(string $expected, array $configData = []): void {
    $configData = array_replace_recursive($this->getDefaultConfigData(), $configData);
    $config = new Config($configData);
    $container = new Container();

    $commands = new CommandsBase($this->composerInfo);
    $commands->setConfig($config);
    $commands->setContainer($container);

    $methodName = 'getEnvironment';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    /** @var string $environment */
    $environment = $method->invokeArgs($commands, []);

    static::assertSame($expected, $environment);
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetEnvironmentVariants(): array {
    return [
      'basic' => [
        ['local', 'default'],
      ],
      'override' => [
        ['prod', 'default'],
        [
          'marvin' => ['environment' => 'prod'],
        ],
      ],
      'override with gitHook' => [
        ['localPreCommit', 'local', 'default'],
        [
          'marvin' => [
            'environment' => 'local',
            'gitHookName' => 'pre-commit',
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetEnvironmentVariants
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $configData
   */
  public function testGetEnvironmentVariants(array $expected, array $configData = []): void {
    $configData = array_replace_recursive($this->getDefaultConfigData(), $configData);
    $container = new Container();

    $this->commands->setConfig(new Config($configData));
    $this->commands->setContainer($container);

    $methodName = 'getEnvironmentVariants';
    $class = new \ReflectionClass($this->commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    $envVariants = $method->invokeArgs($this->commands, []);

    static::assertSame($expected, $envVariants);
  }

}
