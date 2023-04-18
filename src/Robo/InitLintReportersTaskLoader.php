<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\InitLintReportersTask;

trait InitLintReportersTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\InitLintReportersTask
   *
   * @phpstan-param marvin-robo-task-base-options $options
   */
  protected function taskMarvinInitLintReporters(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\InitLintReportersTask $task */
    $task = $this->task(InitLintReportersTask::class);
    $task->setOptions($options);

    return $task;
  }

}
