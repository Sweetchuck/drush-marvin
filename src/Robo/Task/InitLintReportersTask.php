<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils;
use Sweetchuck\LintReport\Reporter\BaseReporter;

class InitLintReportersTask extends BaseTask {

  /**
   * @phpstan-var null|array<string, class-string>
   */
  protected ?array $lintReporters = NULL;

  /**
   * @phpstan-return null|array<string, class-string>
   */
  public function getLintReporters(): ?array {
    return $this->lintReporters;
  }

  /**
   * @phpstan-param null|array<string, class-string> $lintReporters
   */
  public function setLintReporters(?array $lintReporters): static {
    $this->lintReporters = $lintReporters;

    return $this;
  }

  /**
   * @phpstan-return array<string, class-string>
   */
  protected function getLintReportersWithFallback(): array {
    $lintReporters = $this->getLintReporters();

    return $lintReporters ?? BaseReporter::getServices();
  }

  public function runAction():static {
    Utils::initLintReporters(
     $this->getLintReportersWithFallback(),
      $this->getContainer(),
    );

    return $this;
  }

}
