<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit;

use Drupal\marvin\Utils;
use Drupal\Tests\marvin\Helper\DummyOutput;
use Drupal\Tests\marvin\Helper\TaskBuilder;
use Drush\Config\DrushConfig;
use Drush\Drush;
use League\Container\Container as LeagueContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Robo;
use Symfony\Component\Console\Application as SymfonyApplication;

class TaskTestBase extends TestCase {

  protected ContainerInterface $container;

  protected DrushConfig $config;

  protected CollectionBuilder $builder;

  protected TaskBuilder $taskBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    Robo::unsetContainer();
    Drush::unsetContainer();

    $this->container = new LeagueContainer();
    $application = new SymfonyApplication('Marvin - Unit', '2.0.0');
    $this->config = (new DrushConfig())
      ->set('drush.vendor-dir', '.');
    $input = NULL;
    $output = new DummyOutput(DummyOutput::VERBOSITY_DEBUG, FALSE, NULL);

    $this->container->add('container', $this->container);
    $this->container->add('marvin.utils', Utils::class);

    Robo::configureContainer($this->container, $application, $this->config, $input, $output);
    Drush::setContainer($this->container);

    /** @phpstan-ignore-next-line */
    $this->builder = CollectionBuilder::create($this->container, NULL);
    $this->taskBuilder = new TaskBuilder();
    $this->taskBuilder->setContainer($this->container);
    $this->taskBuilder->setBuilder($this->builder);
  }

  /**
   * @phpstan-param mixed[] $expected
   * @phpstan-param mixed[] $actual
   */
  public static function assertRoboTaskLogEntries(array $expected, array $actual): void {
    static::assertSameSize($expected, $actual, 'Number of log messages');

    foreach ($actual as $key => $log) {
      unset($log[2]['task']);
      static::assertSame($expected[$key], $log, "Log entry '$key'");
    }
  }

  protected function getRootDir(string $name = ''): string {
    $class = explode('\\', get_called_class());
    $parts = [
      end($class),
      $name ?: $this->getName(FALSE),
      $this->dataName(),
    ];

    return implode('.', array_filter($parts));
  }

}
