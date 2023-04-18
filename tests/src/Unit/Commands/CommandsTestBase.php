<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Utils;
use Drush\Commands\marvin\CommandsBase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Symfony\Component\Filesystem\Path;

class CommandsTestBase extends TestCase {

  protected static function getMarvinRootDir(): string {
    return Path::canonicalize(__DIR__ . '/../../../..');
  }

  protected vfsStreamDirectory $vfs;

  /**
   * @phpstan-var \Drupal\marvin\ComposerInfo<string, mixed>
   */
  protected ComposerInfo $composerInfo;

  protected string $marvinRootDir = '';

  protected CommandsBase $commands;

  /**
   * @phpstan-var class-string<\Drush\Commands\marvin\CommandsBase>
   */
  protected string $commandsClass = CommandsBase::class;

  protected function setUp(): void {
    parent::setUp();

    $this
      ->setUpVfs()
      ->setUpComposerInfo()
      ->setUpCommands();
  }

  protected function setUpVfs(): static {
    $this->vfs = vfsStream::setup(
      __FUNCTION__,
      NULL,
      [
        'project_01' => [
          'docroot' => [],
          'vendor' => [],
          'composer.json' => '{"name": "drupal/marvin-tester"}',
        ],
      ]
    );

    return $this;
  }

  protected function setUpComposerInfo(): static {
    $this->composerInfo = ComposerInfo::create($this->vfs->url() . '/project_01');

    return $this;
  }

  protected function setUpCommands(): static {
    $this->commands = new $this->commandsClass($this->composerInfo);

    return $this;
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  protected function getDefaultConfigData(): array {
    return [
      'drush' => [
        'vendor-dir' => $this->vfs->url() . '/vendor',
      ],
    ];
  }

  protected function initContainerLintReporters(ContainerInterface $container): static {
    $lintServices = BaseReporter::getServices();
    foreach ($lintServices as $id => $class) {
      Utils::addDefinitionsToContainer(
        [
          $id => [
            'shared' => FALSE,
            'class' => $class,
          ],
        ],
        $container,
      );
    }

    return $this;
  }

}
