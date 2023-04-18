<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\NodeDetectorTask;
use League\Container\ContainerAwareInterface;

trait NodeDetectorTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\NodeDetectorTask
   *
   * @phpstan-param marvin-robo-task-node-detector-options $options
   */
  protected function taskMarvinNodeDetector(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\NodeDetectorTask $task */
    $task = $this->task(NodeDetectorTask::class);

    if ($this instanceof ContainerAwareInterface) {
      $container = $this->getContainer();
      if ($container) {
        $task->setContainer($this->getContainer());
      }
    }

    $task->setOptions($options);

    return $task;
  }

}
