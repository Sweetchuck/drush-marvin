<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Helper;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @method static getErrorOutput()
 */
class DummyOutput extends ConsoleOutput {

  protected static int $instanceCounter = 0;

  public string $output = '';

  public int $instanceId = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    int $verbosity = self::VERBOSITY_NORMAL,
    bool $decorated = NULL,
    OutputFormatterInterface $formatter = NULL,
    bool $isStdError = FALSE,
  ) {
    parent::__construct($verbosity, $decorated, $formatter);
    $this->instanceId = static::$instanceCounter++;

    $errorOutput = $isStdError ?
      $this
      /** @phpstan-ignore-next-line */
      : new static($verbosity, $decorated, $formatter, TRUE);
    $this->setErrorOutput($errorOutput);
  }

  /**
   * {@inheritdoc}
   */
  protected function doWrite(string $message, bool $newline): void {
    $this->output .= $message . ($newline ? PHP_EOL : '');
  }

}
