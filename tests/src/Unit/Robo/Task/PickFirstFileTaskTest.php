<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Robo\State\Data;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\PickFirstFileTask
 * @covers \Drupal\marvin\Robo\Task\BaseTask
 * @covers \Drupal\marvin\Robo\PickFirstFileTaskLoader
 */
class PickFirstFileTaskTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesRunSuccess(): array {
    $rootDir = $this->getRootDir('testRunSuccess');

    return [
      'basic' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'assets' => [
            'x.y' => "vfs://$rootDir.basic/d/c.txt",
            'x.y.dir' => "vfs://$rootDir.basic/d",
          ],
        ],
        [
          'assetNamePrefix' => 'x.',
          'assetNameBase' => 'y',
          'dirSuggestions' => [
            "vfs://$rootDir.basic/a" => TRUE,
            "vfs://$rootDir.basic/b" => FALSE,
            "vfs://$rootDir.basic/c" => TRUE,
            "vfs://$rootDir.basic/d" => TRUE,
          ],
          'fileNameSuggestions' => [
            'a.txt' => TRUE,
            'b.txt' => FALSE,
            'c.txt' => TRUE,
          ],
        ],
        [
          'x.y' => NULL,
        ],
        [
          'a' => [
            'd.txt' => '',
          ],
          'b' => [
            'a.txt' => '',
            'b.txt' => '',
            'c.txt' => '',
          ],
          'c' => [
            'b.txt' => '',
          ],
          'd' => [
            'c.txt' => '',
          ],
        ],
      ],
      'doNotTouchIt' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stateData' => [
            'x.y' => "foo.txt",
          ],
          'assets' => [
            'x.y' => 'foo.txt',
          ],
          'assetsNot' => [
            'x.y.dir',
          ],
        ],
        [
          'assetNamePrefix' => 'x.',
          'assetNameBase' => 'y',
          'dirSuggestions' => [
            "vfs://$rootDir.doNotTouchIt/a",
            "vfs://$rootDir.doNotTouchIt/b",
          ],
          'fileNameSuggestions' => [
            'c.txt',
            'e.txt',
          ],
        ],
        [
          'x.y' => 'foo.txt',
        ],
        [
          'a' => [
            'c.txt' => '',
          ],
          'b' => [
            'e.txt' => '',
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunSuccess
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $options
   * @phpstan-param array<string, mixed> $stateData
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  public function testRunSuccess(array $expected, array $options, array $stateData, array $vfsStructure): void {
    vfsStream::setup($this->getRootDir(), NULL, $vfsStructure);

    $state = new Data('', $stateData);
    $this->taskBuilder->setState($state);

    $task = $this->taskBuilder->taskMarvinPickFirstFile($options);
    $task->setContainer($this->container);
    $task->setState($state);
    $task->original()->setState($state);
    $result = $task->run();

    if (array_key_exists('exitCode', $expected)) {
      static::assertSame($expected['exitCode'], $result->getExitCode());
    }

    /** @var \Drupal\Tests\marvin\Helper\DummyOutput $stdOutput */
    $stdOutput = $this->container->get('output');

    if (array_key_exists('stdOutput', $expected)) {
      static::assertSame($expected['stdOutput'], $stdOutput->output);
    }

    if (array_key_exists('logEntries', $expected)) {
      /** @var \Symfony\Component\ErrorHandler\BufferingLogger $logger */
      $logger = $task->logger();
      static::assertInstanceOf(BufferingLogger::class, $logger);
      static::assertRoboTaskLogEntries($expected['logEntries'], $logger->cleanLogs());
    }

    if (!empty($expected['assets'])) {
      foreach ($expected['assets'] as $key => $value) {
        static::assertSame($value, $result[$key], "Asset '$key'");
      }
    }

    if (!empty($expected['assetsNot'])) {
      foreach ($expected['assetsNot'] as $key) {
        static::assertArrayNotHasKey($key, $result, "Asset not '$key'");
      }
    }

    if (!empty($expected['stateData'])) {
      $taskState = $task->getState();
      foreach ($expected['stateData'] as $key => $value) {
        static::assertSame($value, $taskState[$key], "State '$key'");
      }
    }
  }

}
