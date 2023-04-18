<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask;

trait VersionNumberTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask
   *
   * @phpstan-param marvin-robo-task-version-number-bump-extension-info-options $options
   */
  protected function taskMarvinVersionNumberBumpExtensionInfo(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask $task */
    $task = $this->task(VersionNumberBumpExtensionInfoTask::class);
    $task->setOptions($options);

    return $task;
  }

}
