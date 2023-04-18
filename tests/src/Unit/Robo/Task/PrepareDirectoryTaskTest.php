<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Path;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\PrepareDirectoryTask
 * @covers \Drupal\marvin\Robo\Task\BaseTask
 * @covers \Drupal\marvin\Robo\PrepareDirectoryTaskLoader
 */
class PrepareDirectoryTaskTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesRun(): array {
    return [
      'create' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - Prepare directory] vfs://testRun.create/a',
            ' [Marvin - Prepare directory] Create directory: vfs://testRun.create/a',
            '',
          ]),
        ],
        [],
        [
          'workingDirectory' => 'a',
        ],
      ],
      'exists-empty' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - Prepare directory] vfs://testRun.exists-empty/a',
            ' [Marvin - Prepare directory] Remove all content from directory "vfs://testRun.exists-empty/a"',
            '',
          ]),
        ],
        [
          'a' => [],
        ],
        [
          'workingDirectory' => 'a',
        ],
      ],
      'exists-not-empty' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - Prepare directory] vfs://testRun.exists-not-empty/a',
            ' [Marvin - Prepare directory] Remove all content from directory "vfs://testRun.exists-not-empty/a"',
            '',
          ]),
        ],
        [
          'a' => [
            '.git' => [],
            'b.php' => 'b-content',
            '.htaccess' => 'my-htaccess',
          ],
        ],
        [
          'workingDirectory' => 'a',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRun
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $vfsStructure
   * @phpstan-param array<string, mixed> $options
   */
  public function testRun(array $expected, array $vfsStructure, array $options): void {
    $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
    $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);

    $options['workingDirectory'] = Path::join($vfs->url(), $options['workingDirectory']);

    $task = $this
      ->taskBuilder
      ->taskMarvinPrepareDirectory($options)
      ->setContainer($this->container);

    $result = $task->run();

    static::assertSame(
      $expected['exitCode'],
      $result->getExitCode(),
      'exitCode',
    );
    static::assertDirectoryExists($options['workingDirectory']);
    static::assertCount(
      2,
      new \DirectoryIterator($options['workingDirectory']),
      sprintf('There are no any items in the "%s" directory', $options['workingDirectory'])
    );

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
  }

}
