<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\ArtifactTypesCommands
 * @covers \Drush\Commands\marvin\ArtifactCommandsBase
 * @covers \Drush\Commands\marvin\CommandsBase
 * @covers \Drupal\marvin\CommandDelegatorTrait
 */
class ArtifactTypesCommandsTest extends UnishIntegrationTestCase {

  public function testArtifactTypesJson(): void {
    $options = $this->getCommonCommandLineOptions();
    $options['format'] = 'json';

    $this->drush('marvin:artifact:types', [], $options);

    $expected = [
      'dummy' => [
        'label' => "Dummy - integrationTest",
        'description' => 'Do not use it',
        'id' => 'dummy',
        'weight' => 0,
      ],
    ];

    static::assertSame(
      json_encode($expected, JSON_PRETTY_PRINT) . PHP_EOL,
      $this->getOutputRaw()
    );
  }

  public function testArtifactTypesTable(): void {
    $options = $this->getCommonCommandLineOptions();
    $options['format'] = 'table';
    $this->drush('marvin:artifact:types', [], $options);

    $expected = implode(PHP_EOL, [
      'ID    Label                   Description   ',
      'dummy Dummy - integrationTest Do not use it ',
      '',
    ]);

    static::assertSame($expected, $this->getOutputRaw());
  }

}
