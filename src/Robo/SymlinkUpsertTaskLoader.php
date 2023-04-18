<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\SymlinkUpsertTask;

trait SymlinkUpsertTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\SymlinkUpsertTask
   */
  protected function taskMarvinSymlinkUpsert(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\SymlinkUpsertTask $task */
    $task = $this->task(SymlinkUpsertTask::class);
    $task->setOptions($options);

    return $task;
  }

}
