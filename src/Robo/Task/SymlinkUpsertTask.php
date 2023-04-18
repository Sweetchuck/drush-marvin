<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo\Task;

use Symfony\Component\Filesystem\Filesystem;

class SymlinkUpsertTask extends BaseTask {

  protected string $taskName = 'Marvin - Update symlink';

  protected Filesystem $fs;

  public function __construct(?Filesystem $fs = NULL) {
    $this->fs = $fs ?: new Filesystem();
  }

  protected string $symlinkName = '';

  public function getSymlinkName(): string {
    return $this->symlinkName;
  }

  public function setSymlinkName(string $symlinkName): static {
    $this->symlinkName = $symlinkName;

    return $this;
  }

  protected string $symlinkSrc = '';

  public function getSymlinkSrc(): string {
    return $this->symlinkSrc;
  }

  public function setSymlinkSrc(string $symlinkSrc): static {
    $this->symlinkSrc = $symlinkSrc;

    return $this;
  }

  protected string $symlinkDst = '';

  public function getSymlinkDst(): string {
    return $this->symlinkDst;
  }

  public function setSymlinkDst(string $symlinkDst): static {
    $this->symlinkDst = $symlinkDst;

    return $this;
  }

  /**
   * @phpstan-var marvin-symlink-upsert-action
   */
  protected string $actionOnSourceNotExists = 'create';

  /**
   * @phpstan-return marvin-symlink-upsert-action
   */
  public function getActionOnSourceNotExists(): string {
    return $this->actionOnSourceNotExists;
  }

  /**
   * @phpstan-param marvin-symlink-upsert-action $action
   *   - create: Creates a broken symlink.
   *   - delete: Deletes the symlink if it is already exists.
   *   - ignore: Do nothing.
   */
  public function setActionOnSourceNotExists(string $action): static {
    $this->actionOnSourceNotExists = $action;

    return $this;
  }

  /**
   * @phpstan-param marvin-robo-task-symlink-upsert-options $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('symlinkName', $options)) {
      $this->setSymlinkName($options['symlinkName']);
    }

    if (array_key_exists('symlinkSrc', $options)) {
      $this->setSymlinkSrc($options['symlinkSrc']);
    }

    if (array_key_exists('symlinkDst', $options)) {
      $this->setSymlinkDst($options['symlinkDst']);
    }

    if (array_key_exists('actionOnSourceNotExists', $options)) {
      $this->setActionOnSourceNotExists($options['actionOnSourceNotExists']);
    }

    return $this;
  }

  protected function runHeader(): static {
    $args = [
      'symlinkName' => $this->getSymlinkName(),
      'symlinkSrc' => $this->getSymlinkDst(),
      'symlinkDst' => $this->getSymlinkDst(),
    ];

    $this->printTaskInfo(
      '',
      $args,
    );

    return $this;
  }

  protected function runAction(): static {
    $symlinkName = $this->getSymlinkName();
    $symlinkSrc = $this->getSymlinkSrc();
    $symlinkDst = $this->getSymlinkDst();
    $loggerArgs = [
      'symlinkName' => $symlinkName,
      'symlinkSrc' => $symlinkSrc,
      'symlinkDst' => $symlinkDst,
    ];

    $isLink = is_link($symlinkName);
    if (!$this->fs->exists($symlinkSrc)) {
      switch ($this->getActionOnSourceNotExists()) {
        case 'delete':
          if ($isLink) {
            $this->fs->remove($symlinkName);
          }
          return $this;

        case 'ignore':
          return $this;
      }

      // Symlink can be created,
      // but most likely this is a developer error.
      $this->logger?->warning('Symlink source does not exists: <info>{symlinkSrc}</info>', $loggerArgs);
    }

    if ($isLink && $this->fs->readlink($symlinkName) === $symlinkDst) {
      $this->logger?->info('{symlinkName} already points to {symlinkDst}', $loggerArgs);

      return $this;
    }

    $isExists = $this->fs->exists($symlinkName);
    if ($isExists) {
      if (!$isLink) {
        $this->logger?->warning('{symlinkName} is not a symlink', $loggerArgs);

        return $this;
      }

      $this->fs->remove($symlinkName);
    }

    $this->fs->symlink($symlinkDst, $symlinkName);
    $this->logger?->info(
      ($isExists ?
        '{symlinkName} updated to {symlinkDst}'
        : '{symlinkName} created to {symlinkDst}'
      ),
      $loggerArgs
    );

    return $this;
  }

}
