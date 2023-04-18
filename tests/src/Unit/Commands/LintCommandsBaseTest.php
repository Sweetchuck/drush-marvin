<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drush\Commands\marvin\LintCommandsBase;
use League\Container\Container;
use Robo\Config\Config;
use Sweetchuck\LintReport\Reporter\VerboseReporter;
use Symfony\Component\Yaml\Yaml;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\LintCommandsBase
 * @covers \Drush\Commands\marvin\CommandsBase
 */
class LintCommandsBaseTest extends CommandsTestBase {

  public function testGetConfigValue(): void {
    $configData = [
      'marvin' => [
        'lint' => [
          'my_key' => 'my_value',
        ],
      ],
    ];

    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $commands = new LintCommandsBase($this->composerInfo);
    $commands->setConfig($config);

    $methodName = 'getConfigValue';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('my_value', $method->invokeArgs($commands, ['my_key']));
  }

  public function testGetCustomEventNamePrefix(): void {
    $commands = new LintCommandsBase($this->composerInfo);
    $methodName = 'getCustomEventNamePrefix';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin:lint', $method->invokeArgs($commands, []));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetLintReporterConfigNamesByEnvironmentVariant(): array {
    $marvinRootDir = static::getMarvinRootDir();
    $default = Yaml::parseFile("{$marvinRootDir}/Commands/drush.yml");

    return [
      'empty' => [[], []],
      'default' => [
        [
          'verboseStdOutput',
        ],
        $default,
      ],
    ];
  }

  /**
   * @dataProvider casesGetLintReporterConfigNamesByEnvironmentVariant
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $configData
   */
  public function testGetLintReporterConfigNamesByEnvironmentVariant(array $expected, array $configData = []): void {
    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $commands = new LintCommandsBase($this->composerInfo);
    $commands->setConfig($config);

    $methodName = 'getLintReporterConfigNamesByEnvironmentVariant';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame($expected, $method->invokeArgs($commands, []));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetLintReporters(): array {
    $marvinRootDir = static::getMarvinRootDir();
    $default = Yaml::parseFile("{$marvinRootDir}/Commands/drush.yml");

    return [
      'empty' => [[], []],
      'default' => [
        [
          'verboseStdOutput' => [
            'class' => VerboseReporter::class,
            'filePathStyle' => 'relative',
          ],
        ],
        $default,
      ],
    ];
  }

  /**
   * @dataProvider casesGetLintReporters
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $configData
   */
  public function testGetLintReporters(array $expected, array $configData): void {
    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $container = new Container();
    $this->initContainerLintReporters($container);
    $container->add('lintVerboseReporter', VerboseReporter::class);

    $commands = new LintCommandsBase($this->composerInfo);
    $commands->setConfig($config);
    $commands->setContainer($container);

    $methodName = 'getLintReporters';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    /** @var \Sweetchuck\LintReport\ReporterInterface[] $reporters */
    $reporters = $method->invokeArgs($commands, []);

    static::assertSame(array_keys($expected), array_keys($reporters));
    foreach ($reporters as $name => $reporter) {
      if (array_key_exists('class', $expected[$name])) {
        static::assertSame($expected[$name]['class'], get_class($reporter));
      }

      if (array_key_exists('filePathStyle', $expected[$name])) {
        static::assertSame($expected[$name]['filePathStyle'], $reporter->getFilePathStyle());
      }
    }
  }

  public function testParseLintReporterConfigs(): void {
    $container = new Container();
    $this->initContainerLintReporters($container);
    $container
      ->add('lintVerboseReporter', VerboseReporter::class)
      ->setShared(FALSE);

    $config = new Config($this->getDefaultConfigData());

    $commands = new LintCommandsBase($this->composerInfo);
    $commands->setConfig($config);
    $commands->setContainer($container);

    $methodName = 'parseLintReporterConfigs';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    $reporterConfigs = [
      'verboseA' => [
        'service' => 'lintVerboseReporter',
        'options' => [
          'filePathStyle' => 'relative',
        ],
      ],
      'verboseB' => [
        'service' => 'lintVerboseReporter',
        'options' => [
          'filePathStyle' => 'absolute',
        ],
      ],
    ];

    /** @var \Sweetchuck\LintReport\ReporterInterface[] $reporters */
    $reporters = $method->invokeArgs($commands, [$reporterConfigs]);

    static::assertInstanceOf(VerboseReporter::class, $reporters['verboseA']);
    static::assertSame('relative', $reporters['verboseA']->getFilePathStyle());

    static::assertInstanceOf(VerboseReporter::class, $reporters['verboseB']);
    static::assertSame('absolute', $reporters['verboseB']->getFilePathStyle());
  }

  public function testParseLintReporterConfig(): void {
    $container = new Container();
    $this->initContainerLintReporters($container);
    $container->add('lintVerboseReporter', VerboseReporter::class);

    $config = new Config($this->getDefaultConfigData());

    $commands = new LintCommandsBase($this->composerInfo);
    $commands->setConfig($config);
    $commands->setContainer($container);

    $methodName = 'parseLintReporterConfig';
    $class = new \ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    $reporterConfig = [
      'service' => 'lintVerboseReporter',
      'options' => [
        'filePathStyle' => 'relative',
      ],
    ];

    /** @var \Sweetchuck\LintReport\ReporterInterface $reporter */
    $reporter = $method->invokeArgs($commands, [$reporterConfig]);

    static::assertInstanceOf(VerboseReporter::class, $reporter);
    static::assertSame('relative', $reporter->getFilePathStyle());
  }

}
