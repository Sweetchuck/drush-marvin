<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @property null|\Psr\Log\LoggerInterface $logger
 */
trait CommandDelegatorTrait {

  protected string $customEventNamePrefix = 'marvin';

  protected function getCustomEventNamePrefix(): string {
    return $this->customEventNamePrefix;
  }

  protected function getCustomEventName(string $eventBaseName): string {
    return $this->getCustomEventNamePrefix() . ($eventBaseName ? ":{$eventBaseName}" : '');
  }

  /**
   * @phpstan-param array<mixed> $args
   *
   * @todo Find a better name for this method.
   * @todo Make this method universal.
   * @todo Trigger a *.alter event.
   */
  protected function delegate(string $eventBaseName, ...$args): CollectionBuilder {
    $cb = $this->collectionBuilder();

    $taskDefinitions = $this->delegateCollectTaskDefinitions($eventBaseName, $args);
    $this
      ->delegateLogTaskDefinitions($eventBaseName, $taskDefinitions)
      ->delegatePrepareCollectionBuilder($cb, $eventBaseName, $args)
      ->delegateAddTasks($cb, $taskDefinitions);

    return $cb;
  }

  /**
   * @phpstan-param array<mixed> $args
   *
   * @phpstan-return array<string, marvin-task-definition>
   */
  protected function delegateCollectTaskDefinitions(string $eventBaseName, array $args): array {
    $eventName = $this->getCustomEventName($eventBaseName);
    $this->getLogger()->debug(
      'Collecting task definitions for event "<info>{eventName}</info>"',
      ['eventName' => $eventName],
    );

    $taskDefinitions = [];
    /** @var callable[] $eventHandlers */
    $eventHandlers = $this->getCustomEventHandlers($eventName);
    foreach ($eventHandlers as $eventHandler) {
      $provider = Utils::callableToString($eventHandler);
      $tasks = $eventHandler($this->input(), $this->output(), ...$args);
      foreach (array_keys($tasks) as $key) {
        $tasks[$key] += [
          'provider' => $provider,
          'weight' => 0,
          'description' => '',
        ];
      }
      $taskDefinitions += $tasks;
    }

    $comparer = new ArrayValueComparer();
    $comparer->setKeys([
      'weight' => [
        'default' => 0,
      ],
    ]);
    uasort($taskDefinitions, $comparer);

    return $taskDefinitions;
  }

  /**
   * @phpstan-param array<mixed> $args
   *
   * @SuppressWarnings(UnusedFormalParameter)
   */
  protected function delegatePrepareCollectionBuilder(
    CollectionBuilder $cb,
    string $eventBaseName,
    array $args,
  ): static {
    return $this;
  }

  /**
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  protected function delegateAddTasks(CollectionBuilder $cb, array $taskDefinitions): static {
    $taskTypes = ['task', 'rollback', 'completion'];
    foreach ($taskDefinitions as $taskDefinition) {
      foreach ($taskTypes as $taskType) {
        if (isset($taskDefinition[$taskType])) {
          $this->delegateAddTask($cb, $taskDefinition[$taskType], $taskType);
        }
      }
    }

    return $this;
  }

  /**
   * @param \Robo\Collection\CollectionBuilder $cb
   * @param \Robo\Contract\TaskInterface|\Closure $task
   * @param string $taskType
   */
  protected function delegateAddTask(CollectionBuilder $cb, $task, string $taskType): static {
    if ($taskType === 'rollback' || $taskType === 'completion') {
      $method = $task instanceof TaskInterface ? $taskType : "{$taskType}Code";
      $cb->$method($task);

      return $this;
    }

    if ($task instanceof TaskInterface) {
      $cb->addTask($task);

      return $this;
    }

    $cb->addCode($task);

    return $this;
  }

  /**
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  protected function delegateLogTaskDefinitions(string $eventBaseName, array $taskDefinitions): static {
    $logger = $this->getLogger();
    $logArgs = [
      'eventName' => $this->getCustomEventName($eventBaseName),
    ];

    if (!$taskDefinitions) {
      $logger->notice(
        'there are no tasks for event: {eventName}',
        $logArgs,
      );

      return $this;
    }

    $output = new BufferedOutput();
    $table = Utils::taskDefinitionsAsTable($taskDefinitions, $output);
    $table->render();
    $logArgs['taskDefinitionsTable'] = $output->fetch();
    $this->getLogger()->notice(
      "tasks to run on event: {eventName}\n{taskDefinitionsTable}",
      $logArgs,
    );

    return $this;
  }

}
