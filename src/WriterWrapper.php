<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Filesystem\Filesystem;

class WriterWrapper {

  protected ?OutputInterface $destinationInstance = NULL;

  /**
   * File handler.
   *
   * @var null|resource
   */
  protected $destinationResource = NULL;

  /**
   * @var null|string|\Symfony\Component\Console\Output\OutputInterface
   */
  protected $destination = NULL;

  /**
   * @return null|string|\Symfony\Component\Console\Output\OutputInterface
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * @param null|string|\Symfony\Component\Console\Output\OutputInterface $destination
   */
  public function setDestination($destination): static {
    $this->reset();
    $this->destination = $destination;

    return $this;
  }

  protected string $destinationMode = 'w';

  public function getDestinationMode(): string {
    return $this->destinationMode;
  }

  public function setDestinationMode(string $destinationMode): static {
    $this->reset();
    $this->destinationMode = $destinationMode;

    return $this;
  }

  /**
   * @see \Symfony\Component\Console\Output\OutputInterface::write
   *
   * @phpstan-param string|iterable<string> $messages
   */
  public function write(string|iterable $messages, bool $newLine = FALSE, int $options = 0): static {
    $this->init();
    // @todo Error if the not initialized.
    if ($this->destinationInstance) {
      $this->destinationInstance->write($messages, $newLine, $options);
    }

    return $this;
  }

  /**
   * Close the destination resource if it was opened here.
   */
  public function close(): static {
    if ($this->destinationResource) {
      fclose($this->destinationResource);
    }

    return $this;
  }

  /**
   * Initialize the output destination instance.
   */
  protected function init(): static {
    if ($this->destinationInstance) {
      return $this;
    }

    $destination = $this->getDestination();
    if (is_string($destination)) {
      $fs = new Filesystem();
      $fs->mkdir(dirname($destination));

      $this->destinationResource = fopen($destination, $this->getDestinationMode()) ?: NULL;
      if ($this->destinationResource === NULL) {
        throw new \RuntimeException("File '$destination' could not be opened.");
      }

      $this->destinationInstance = new StreamOutput(
        $this->destinationResource,
        OutputInterface::VERBOSITY_NORMAL,
        FALSE
      );

      return $this;
    }

    $this->destinationInstance = $destination;

    return $this;
  }

  protected function reset(): static {
    $this->destinationInstance = NULL;
    $this->destinationResource = NULL;

    return $this;
  }

}
