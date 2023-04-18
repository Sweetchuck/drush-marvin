<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Path;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask
 * @covers \Drupal\marvin\Robo\Task\BaseTask
 * @covers \Drupal\marvin\Robo\VersionNumberTaskLoader
 */
class VersionNumberBumpExtensionInfoTaskTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesRunSuccess(): array {
    $composerJsonBefore = implode(PHP_EOL, [
      '{',
      '    "name": "drupal/dummy_m1",',
      '    "version": "1.0.0",',
      '    "type": "drupal-module"',
      '}',
      '',
    ]);

    $composerJsonAfter = implode(PHP_EOL, [
      '{',
      '    "name": "drupal/dummy_m1",',
      '    "version": "1.1.0",',
      '    "type": "drupal-module"',
      '}',
      '',
    ]);

    $infoYamlBefore = implode(PHP_EOL, [
      'type: module',
      'version: 8.x-1.0',
      'core: 8.x',
      '',
    ]);

    $infoYamlAfter = implode(PHP_EOL, [
      'type: module',
      'version: 8.x-1.1',
      'core: 8.x',
      '',
    ]);

    return [
      'all' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - Bump version number - extension info] Bump version number to "8.x-1.1" in "vfs://testRunSuccess.all" directory.',
            ' [Marvin - Bump version number - extension info] Update version number to "8.x-1.1" in "vfs://testRunSuccess.all/dummy_m1.info.yml" file.',
            ' [Marvin - Bump version number - extension info] Update version number to "1.1.0" in "vfs://testRunSuccess.all/composer.json" file.',
            '',
          ]),
          'files' => [
            'composer.json' => $composerJsonAfter,
            'dummy_m1.info.yml' => $infoYamlAfter,
          ],
        ],
        [
          'composer.json' => $composerJsonBefore,
          'dummy_m1.info.yml' => $infoYamlBefore,
        ],
        [
          'versionNumber' => '8.x-1.1',
        ],
      ],
      'bumpExtensionInfo.false' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - Bump version number - extension info] Bump version number to "8.x-1.1" in "vfs://testRunSuccess.bumpExtensionInfo.false" directory.',
            ' [Marvin - Bump version number - extension info] Skip update version number to "8.x-1.1" in "vfs://testRunSuccess.bumpExtensionInfo.false/*.info.yml" files.',
            ' [Marvin - Bump version number - extension info] Update version number to "1.1.0" in "vfs://testRunSuccess.bumpExtensionInfo.false/composer.json" file.',
            '',
          ]),
          'files' => [
            'composer.json' => $composerJsonAfter,
            'dummy_m1.info.yml' => $infoYamlBefore,
          ],
        ],
        [
          'composer.json' => $composerJsonBefore,
          'dummy_m1.info.yml' => $infoYamlBefore,
        ],
        [
          'versionNumber' => '8.x-1.1',
          'bumpExtensionInfo' => FALSE,
        ],
      ],
      'bumpComposerJson.false' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - Bump version number - extension info] Bump version number to "8.x-1.1" in "vfs://testRunSuccess.bumpComposerJson.false" directory.',
            ' [Marvin - Bump version number - extension info] Update version number to "8.x-1.1" in "vfs://testRunSuccess.bumpComposerJson.false/dummy_m1.info.yml" file.',
            ' [Marvin - Bump version number - extension info] Skip update version number to "1.1.0" in "vfs://testRunSuccess.bumpComposerJson.false/composer.json" file.',
            '',
          ]),
          'files' => [
            'composer.json' => $composerJsonBefore,
            'dummy_m1.info.yml' => $infoYamlAfter,
          ],
        ],
        [
          'composer.json' => $composerJsonBefore,
          'dummy_m1.info.yml' => $infoYamlBefore,
        ],
        [
          'versionNumber' => '8.x-1.1',
          'bumpComposerJson' => FALSE,
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunSuccess
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $vfsStructure
   * @phpstan-param array<string, mixed> $options
   */
  public function testRunSuccess(array $expected, array $vfsStructure, array $options): void {
    $expected += [
      'files' => [],
    ];

    $options += [
      'packagePath' => '.',
    ];

    $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
    $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);

    $options['packagePath'] = Path::join($vfs->url(), $options['packagePath']);

    $task = $this
      ->taskBuilder
      ->taskMarvinVersionNumberBumpExtensionInfo($options);

    $result = $task->run();

    static::assertSame($expected['exitCode'], $result->getExitCode());

    /** @var \Drupal\Tests\marvin\Helper\DummyOutput $stdOutput */
    $stdOutput = $this->container->get('output');
    if (array_key_exists('stdOutput', $expected)) {
      static::assertSame(
        $expected['stdOutput'],
        $stdOutput->output,
        'stdOutput',
      );
    }

    if (array_key_exists('stdError', $expected)) {
      static::assertSame(
        $expected['stdError'],
        $stdOutput->getErrorOutput()->output,
        'stdError',
      );
    }

    foreach ($expected['files'] as $fileName => $fileContent) {
      static::assertStringEqualsFile(
        Path::join($options['packagePath'], $fileName),
        $fileContent
      );
    }
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesRunFail(): array {
    return [
      'packagePath.empty' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_PACKAGE_PATH_EMPTY,
        ],
      ],
      'packagePath.not-exists' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_PACKAGE_PATH_NOT_EXISTS,
        ],
        [
          'packagePath' => 'a',
        ],
      ],
      'versionNumber.empty' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_VERSION_NUMBER_EMPTY,
        ],
        [
          'packagePath' => 'a',
        ],
        [
          'a' => [],
        ],
      ],
      'versionNumber.invalid' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_VERSION_NUMBER_INVALID,
        ],
        [
          'packagePath' => 'a',
          'versionNumber' => 'b',
        ],
        [
          'a' => [],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunFail
   *
   * @phpstan-param array<string, mixed> $options
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  public function testRunFail(mixed $expected, array $options = [], array $vfsStructure = []): void {
    if (array_key_exists('class', $expected)) {
      $this->expectException($expected['class']);
    }

    if (array_key_exists('message', $expected)) {
      $this->expectExceptionMessage($expected['message']);
    }

    if (array_key_exists('code', $expected)) {
      $this->expectExceptionCode($expected['code']);
    }

    if (!empty($options['packagePath'])) {
      $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
      $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);
      $options['packagePath'] = Path::join($vfs->url(), $options['packagePath']);
    }

    $this
      ->taskBuilder
      ->taskMarvinVersionNumberBumpExtensionInfo($options)
      ->run();
  }

}
