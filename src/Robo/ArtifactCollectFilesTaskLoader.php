<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\ArtifactCollectFilesTask;

trait ArtifactCollectFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\ArtifactCollectFilesTask
   *
   * @phpstan-param marvin-robo-task-artifact-collect-files-options $options
   */
  protected function taskMarvinArtifactCollectFiles(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\ArtifactCollectFilesTask $task */
    $task = $this->task(ArtifactCollectFilesTask::class);
    $task->setOptions($options);

    return $task;
  }

}
