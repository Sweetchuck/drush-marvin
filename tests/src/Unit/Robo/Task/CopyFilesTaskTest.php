<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\CopyFilesTask
 * @covers \Drupal\marvin\Robo\Task\BaseTask
 * @covers \Drupal\marvin\Robo\CopyFilesTaskLoader
 */
class CopyFilesTaskTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesRunSuccess(): array {
    $fileContent = 'a';

    $logEntry = [
      'debug',
      'copy: {srcDir} {dstDir} {file}',
      [
        'srcDir' => 'vfs://testRunSuccess/mySrc',
        'dstDir' => 'vfs://testRunSuccess/myDst',
        'file' => NULL,
        'name' => 'Marvin - Copy files',
      ],
    ];

    return [
      'empty' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => " [Marvin - Copy files] \n",
          'logEntries' => [
            [
              'notice',
              '',
              [
                'name' => 'Marvin - Copy files',
              ],
            ],
          ],
        ],
        [],
        [],
      ],
      'basic' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => implode("\n", [
            ' [Marvin - Copy files] ',
            ' [Marvin - Copy files] copy: vfs://testRunSuccess/mySrc vfs://testRunSuccess/myDst a.txt',
            ' [Marvin - Copy files] copy: vfs://testRunSuccess/mySrc vfs://testRunSuccess/myDst b/c.txt',
            ' [Marvin - Copy files] copy: vfs://testRunSuccess/mySrc vfs://testRunSuccess/myDst d.txt',
            '',
          ]),
          'logEntries' => [
            [
              'notice',
              '',
              [
                'name' => 'Marvin - Copy files',
              ],
            ],
            array_replace_recursive($logEntry, [2 => ['file' => 'a.txt']]),
            array_replace_recursive($logEntry, [2 => ['file' => 'b/c.txt']]),
            array_replace_recursive($logEntry, [2 => ['file' => 'd.txt']]),
          ],
          'files' => [
            'a.txt' => TRUE,
            'b/c.txt' => TRUE,
            'b/d.txt' => FALSE,
            'd.txt' => TRUE,
          ],
        ],
        [
          'mySrc' => [
            'a.txt' => $fileContent,
            'b' => [
              'c.txt' => $fileContent,
              'd.txt' => $fileContent,
            ],
            'd.txt' => $fileContent,
          ],
        ],
        [
          'srcDir' => 'mySrc',
          'dstDir' => 'myDst',
          'files' => [
            'a.txt',
            'b/c.txt',
          ],
          'filesSpl' => [
            'd.txt',
          ],
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
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $rootDir = $vfs->url();
    if (!empty($options['srcDir'])) {
      $options['srcDir'] = $rootDir . '/' . $options['srcDir'];
    }

    if (!empty($options['dstDir'])) {
      $options['dstDir'] = $rootDir . '/' . $options['dstDir'];
    }

    if (array_key_exists('filesSpl', $options)) {
      foreach ($options['filesSpl'] as $relativePathName) {
        $options['files']['spl'][] = new SplFileInfo("{$options['srcDir']}/$relativePathName", dirname($relativePathName), $relativePathName);
      }

      unset($options['filesSpl']);
    }

    $task = $this
      ->taskBuilder
      ->taskMarvinCopyFiles($options);
    $task->setContainer($this->container);

    $result = $task->run();

    if (array_key_exists('exitCode', $expected)) {
      static::assertSame($expected['exitCode'], $result->getExitCode());
    }

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

    if (!empty($expected['files'])) {
      foreach ($expected['files'] as $fileName => $exists) {
        $fileNameAbsolute = "{$options['dstDir']}/$fileName";
        static::assertSame(
          $exists,
          file_exists($fileNameAbsolute),
          sprintf('file exists: %s; file name: "%s";', var_export($exists, TRUE), $fileNameAbsolute)
        );
      }
    }
  }

}
