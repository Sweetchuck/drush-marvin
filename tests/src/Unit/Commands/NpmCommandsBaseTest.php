<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drush\Commands\marvin\NpmCommandsBase;
use ReflectionClass;
use Robo\Config\Config;

class NpmCommandsBaseTest extends CommandsTestBase {

  public function testGetClassKey(): void {
    $commands = new NpmCommandsBase($this->composerInfo);

    $methodName = 'getClassKey';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin.npm.a', $method->invokeArgs($commands, ['a']));
  }

  public function testGetConfigValue(): void {
    $configData = [
      'marvin' => [
        'npm' => [
          'my_key' => 'my_value',
        ],
      ],
    ];

    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $commands = new NpmCommandsBase($this->composerInfo);
    $commands->setConfig($config);

    $methodName = 'getConfigValue';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('my_value', $method->invokeArgs($commands, ['my_key']));
  }

  public function testGetCustomEventNamePrefix(): void {
    $commands = new NpmCommandsBase($this->composerInfo);
    $methodName = 'getCustomEventNamePrefix';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin:npm', $method->invokeArgs($commands, []));
  }

}
