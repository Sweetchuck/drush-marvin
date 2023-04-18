<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Tests\marvin\Helper\DummyOutput;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use Drush\Commands\marvin\ArtifactTypesCommands;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\ArtifactTypesCommands
 * @covers \Drush\Commands\marvin\ArtifactCommandsBase
 * @covers \Drush\Commands\marvin\CommandsBase
 */
class ArtifactTypesCommandsTest extends TaskTestBase {

  public function testGetCustomEventNamePrefix(): void {
    $reflection = new \ReflectionClass(ArtifactTypesCommands::class);
    $method = $reflection->getMethod('getCustomEventNamePrefix');
    $method->setAccessible(TRUE);
    $commands = new ArtifactTypesCommands();

    static::assertSame('marvin:artifact', $method->invoke($commands));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetCustomEventName(): array {
    return [
      'empty' => ['marvin:artifact', ''],
      'something' => ['marvin:artifact:something', 'something'],
    ];
  }

  /**
   * @dataProvider casesGetCustomEventName
   */
  public function testGetCustomEventName(string $expected, string $eventBaseName): void {
    $reflection = new \ReflectionClass(ArtifactTypesCommands::class);
    $method = $reflection->getMethod('getCustomEventName');
    $method->setAccessible(TRUE);
    $commands = new ArtifactTypesCommands();

    static::assertSame($expected, $method->invoke($commands, $eventBaseName));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesArtifactTypes(): array {
    return [
      'basic' => [
        [
          'b' => [
            'label' => 'A',
            'id' => 'b',
            'weight' => 0,
          ],
          'c' => [
            'label' => 'A',
            'weight' => 1,
            'id' => 'c',
          ],
          'a' => [
            'label' => 'A',
            'weight' => 2,
            'id' => 'a',
          ],
        ],
        'drupal-module',
        [
          'drupal-module' => [
            'a' => [
              'label' => 'A',
              'weight' => 2,
            ],
            'b' => [
              'label' => 'A',
            ],
            'c' => [
              'label' => 'A',
              'weight' => 1,
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesArtifactTypes
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $registeredArtifactTypes
   * @phpstan-param array<string, mixed> $options
   */
  public function testArtifactTypes(array $expected, string $projectType, array $registeredArtifactTypes, array $options = []): void {
    $hookManager = new HookManager();
    $hookManager->add(
      $this->createHookCallbackForMarvinArtifactTypes($registeredArtifactTypes),
      'on-event',
      'marvin:artifact:types'
    );

    $this->config->set('marvin.projectType', $projectType);

    $commands = new ArtifactTypesCommands();
    $commands->setConfig($this->config);
    $commands->setHookManager($hookManager);

    $actual = $commands->cmdArtifactTypesExecute($options);

    static::assertSame($expected, $actual);
  }

  public function testHookAlterMarvinArtifactTypes(): void {
    $commands = new ArtifactTypesCommands();

    $cdEmpty = $this->createCommandData();
    $cdTable = $this->createCommandData(['format' => 'table']);

    static::assertSame([], $commands->cmdArtifactTypesAlter([], $cdEmpty));
    static::assertSame(['a' => 'b'], $commands->cmdArtifactTypesAlter(['a' => 'b'], $cdEmpty));

    $actual = $commands->cmdArtifactTypesAlter(['a' => 'b'], $cdTable);
    static::assertInstanceOf(RowsOfFields::class, $actual);
    static::assertSame(['a' => 'b'], $actual->getArrayCopy());
  }

  /**
   * @phpstan-param array<string, mixed> $options
   * @phpstan-param array<string, mixed> $args
   */
  protected function createCommandData(array $options = [], array $args = []): CommandData {
    $inputDefinition = new InputDefinition([
      'format' => new InputOption('format', NULL, InputOption::VALUE_OPTIONAL),
    ]);

    $commandData = new CommandData(
      new AnnotationData([]),
      new ArgvInput([], $inputDefinition),
      new DummyOutput()
    );

    foreach ($options as $name => $value) {
      $commandData->input()->setOption($name, $value);
    }

    foreach ($args as $name => $value) {
      $commandData->input()->setArgument($name, $value);
    }

    return $commandData;
  }

  /**
   * @phpstan-param array<string, mixed> $artifactTypes
   */
  protected function createHookCallbackForMarvinArtifactTypes(array $artifactTypes): callable {
    return function (string $projectType) use ($artifactTypes): array {
      return $artifactTypes[$projectType] ?? [];
    };
  }

}
