<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drush\Commands\marvin\StatusReportCommands;
use Robo\Config\Config;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\StatusReportCommands
 * @covers \Drush\Commands\marvin\CommandsBase
 */
class StatusReportCommandsTest extends CommandsTestBase {

  public function testGetClassKey(): void {
    $commands = new StatusReportCommands($this->composerInfo);

    $methodName = 'getClassKey';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin.statusReport.a', $method->invokeArgs($commands, ['a']));
  }

  public function testGetConfigValue(): void {
    $configData = [
      'marvin' => [
        'statusReport' => [
          'my_key' => 'my_value',
        ],
      ],
    ];

    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $commands = new StatusReportCommands($this->composerInfo);
    $commands->setConfig($config);

    $methodName = 'getConfigValue';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('my_value', $method->invokeArgs($commands, ['my_key']));
  }

  public function testGetCustomEventNamePrefix(): void {
    $commands = new StatusReportCommands($this->composerInfo);
    $methodName = 'getCustomEventNamePrefix';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin:status-report', $method->invokeArgs($commands, []));
  }

}
