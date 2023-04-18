<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Helper;

use Symfony\Component\Process\Process;

class ProcessResult {

  public int $exitCode = 0;

  public string $stdOutput = '';

  public string $stdError = '';

  public static function createFromProcess(Process $process): static {
    /** @phpstan-ignore-next-line */
    $result = new static();
    $result->exitCode = $process->getExitCode();
    $result->stdOutput = $process->getOutput();
    $result->stdError = $process->getErrorOutput();

    return $result;
  }

}
