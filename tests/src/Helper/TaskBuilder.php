<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Helper;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;
use Drupal\marvin\Robo\ArtifactCollectFilesTaskLoader;
use Drupal\marvin\Robo\CopyFilesTaskLoader;
use Drupal\marvin\Robo\NodeDetectorTaskLoader;
use Drupal\marvin\Robo\PickFirstFileTaskLoader;
use Drupal\marvin\Robo\PrepareDirectoryTaskLoader;
use Drupal\marvin\Robo\VersionNumberTaskLoader;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Common\OutputAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Robo\TaskAccessor;

class TaskBuilder implements
  BuilderAwareInterface,
  ConfigAwareInterface,
  ContainerAwareInterface,
  LoggerAwareInterface,
  OutputAwareInterface,
  StateAwareInterface {

  use TaskAccessor;
  use ConfigAwareTrait;
  use ContainerAwareTrait;
  use LoggerAwareTrait;
  use OutputAwareTrait;
  use StateAwareTrait;

  use ArtifactCollectFilesTaskLoader {
    taskMarvinArtifactCollectFiles as public;
  }

  use CopyFilesTaskLoader {
    taskMarvinCopyFiles as public;
  }

  use NodeDetectorTaskLoader {
    taskMarvinNodeDetector as public;
  }

  use PickFirstFileTaskLoader {
    taskMarvinPickFirstFile as public;
  }

  use PrepareDirectoryTaskLoader {
    taskMarvinPrepareDirectory as public;
  }

  use VersionNumberTaskLoader {
    taskMarvinVersionNumberBumpExtensionInfo as public;
  }

  public function getLogger(): LoggerInterface {
    return $this->logger;
  }

  public function collectionBuilder(): CollectionBuilder {
    /** @phpstan-ignore-next-line */
    return CollectionBuilder::create($this->getContainer(), NULL);
  }

}
