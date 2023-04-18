<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\Robo\ArtifactCollectFilesTaskLoader;
use Drupal\marvin\Robo\CopyFilesTaskLoader;
use Drupal\marvin\Robo\PrepareDirectoryTaskLoader;

class ArtifactCommandsBase extends CommandsBase {

  use ArtifactCollectFilesTaskLoader;
  use CopyFilesTaskLoader;
  use PrepareDirectoryTaskLoader;

  protected static string $classKeyPrefix = 'marvin.artifact';

  protected string $customEventNamePrefix = 'marvin:artifact';

}
