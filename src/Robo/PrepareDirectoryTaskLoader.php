<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\PrepareDirectoryTask;

trait PrepareDirectoryTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\PrepareDirectoryTask
   *
   * @phpstan-param marvin-robo-task-perpare-directory-options $options
   */
  protected function taskMarvinPrepareDirectory(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\PrepareDirectoryTask $task */
    $task = $this->task(PrepareDirectoryTask::class);
    $task->setOptions($options);

    return $task;
  }

}
