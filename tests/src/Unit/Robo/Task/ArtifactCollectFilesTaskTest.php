<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\String\UnicodeString;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\ArtifactCollectFilesTask
 * @covers \Drupal\marvin\Robo\Task\BaseTask
 * @covers \Drupal\marvin\Robo\ArtifactCollectFilesTaskLoader
 */
class ArtifactCollectFilesTaskTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesRunSuccess(): array {
    $cases = [];
    foreach (['module', 'theme', 'drush', 'profile'] as $projectType) {
      $files = $this->getDrupalExtensionFiles('dummy_m1', $projectType);
      $cases["$projectType.basic"] = [$files['expected'], $files['structure']];
    }

    return $cases;
  }

  /**
   * @dataProvider casesRunSuccess
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  public function testRunSuccess(array $expected, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $this->config->set('marvin.buildDir', 'build');

    $options = [
      'composerJsonFileName' => 'composer.json',
      'packagePath' => $vfs->url(),
    ];

    $result = $this
      ->taskBuilder
      ->taskMarvinArtifactCollectFiles($options)
      ->run();

    $actual = [];
    /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
    foreach ($result['files'] as $files) {
      foreach ($files as $file) {
        $actual[] = $file->getRelativePathname();
      }
    }

    sort($expected);
    sort($actual);

    static::assertSame($expected, $actual);
  }

  /**
   * @phpstan-return array{expected: string[], structure: array<string, mixed>}
   */
  protected function getDrupalExtensionFiles(string $name, string $type): array {
    $nameUpperCamel = (new UnicodeString("a_$name"))
      ->camel()
      ->trimPrefix('a')
      ->toString();

    $fileContent = 'a';

    $mainFileName = $type === 'drush' ? "$name.module" : "$name.$type";

    return [
      'expected' => [
        'config/install/a.yml',
        'config/install/b.yml',
        'config/optional/c.yml',
        'config/optional/d.yml',
        "src/Entity/$nameUpperCamel.php",
        "src/Entity/{$nameUpperCamel}Interface.php",
        "src/Tests/{$nameUpperCamel}TestBase.php",
        "src/Tests/{$nameUpperCamel}Test.php",
        'templates/e.html.twig',
        'tests/src/Functional/f.php',
        'tests/src/Kernel/k.php',
        'tests/src/Unit/u.php',
        'css/main.css',
        'css/foo.md',
        'js/main.js',
        'js/main.td.ts',
        "$name.info.yml",
        $mainFileName,
        "$name.token.inc",
        "$name.theme.inc",
        'composer.json',
        'README.md',
      ],
      'structure' => array_replace_recursive(
        $this->getStructureOfTheUndesirableFiles(),
        [
          'config' => [
            'install' => [
              'a.yml' => $fileContent,
              'b.yml' => $fileContent,
            ],
            'optional' => [
              'c.yml' => $fileContent,
              'd.yml' => $fileContent,
            ],
          ],
          'src' => [
            'Entity' => [
              "$nameUpperCamel.php" => $fileContent,
              "{$nameUpperCamel}Interface.php" => $fileContent,
            ],
            'Tests' => [
              "{$nameUpperCamel}TestBase.php" => $fileContent,
              "{$nameUpperCamel}Test.php" => $fileContent,
            ],
          ],
          'templates' => [
            'e.html.twig' => $fileContent,
          ],
          'tests' => [
            'src' => [
              'Functional' => [
                'f.php' => $fileContent,
              ],
              'Kernel' => [
                'k.php' => $fileContent,
              ],
              'Unit' => [
                'u.php' => $fileContent,
              ],
            ],
          ],
          'css' => [
            'main.css' => $fileContent,
            'foo.md' => $fileContent,
          ],
          'js' => [
            'main.js' => $fileContent,
            'main.td.ts' => $fileContent,
          ],
          "$name.info.yml" => $fileContent,
          $mainFileName => $fileContent,
          "$name.token.inc" => $fileContent,
          "$name.theme.inc" => $fileContent,
          'README.md' => $fileContent,
          'composer.json' => json_encode([
            'type' => "drupal-$type",
          ]),
        ]
      ),
    ];
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  protected function getStructureOfTheUndesirableFiles(): array {
    $fileContent = 'a';
    $dirContent = [
      'a.php' => $fileContent,
      'vcs.xml' => $fileContent,
    ];

    return [
      '.idea' => $dirContent,
      '.phpstorm.meta.php' => $fileContent,
      'test.___jb_old___' => $fileContent,
      '.kdev4' => $dirContent,
      'my.kdev4' => $fileContent,
      '.DS_Store' => $fileContent,
      '.directory' => $fileContent,
      'css' => [
        'main.scss' => $fileContent,
        'main.sass' => $fileContent,
        'main.css.map' => $fileContent,
      ],
      'js' => [
        'main.ts' => $fileContent,
        'main.js.map' => $fileContent,
      ],
      // @todo Add more files.
    ];
  }

}
