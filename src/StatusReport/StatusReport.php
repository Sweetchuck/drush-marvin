<?php

declare(strict_types=1);

namespace Drupal\marvin\StatusReport;

use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\Utils;

/**
 * @template           TKey   of string
 * @template-covariant TValue of \Drupal\marvin\StatusReport\StatusReportEntryInterface
 *
 * @implements \Drupal\marvin\StatusReport\StatusReportInterface<TKey, TValue>
 */
class StatusReport implements StatusReportInterface {

  /**
   * @var \Drupal\marvin\StatusReport\StatusReportEntryInterface[]
   */
  protected array $entries = [];

  /**
   * @phpstan-var marvin-rfc-log-level
   */
  protected int $lowestSeverityAssError = RfcLogLevel::ERROR;

  /**
   * @phpstan-return marvin-rfc-log-level
   */
  public function getLowestSeverityAsError(): int {
    return $this->lowestSeverityAssError;
  }

  /**
   * @phpstan-param marvin-rfc-log-level $severity
   */
  public function setLowestSeverityAsError(int $severity): static {
    $this->lowestSeverityAssError = $severity;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return count($this->entries);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Iterator {
    return new \ArrayIterator($this->entries);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, marvin-status-report-entry-export>
   */
  public function jsonSerialize(): array {
    $data = [];
    foreach ($this->entries as $entry) {
      $data[$entry->getId()] = $entry->jsonSerialize();
    }

    return $data;
  }

  /**
   * @phpstan-return array<string, marvin-status-report-entry-export>
   */
  public function getOutputData() {
    return $this->jsonSerialize();
  }

  /**
   * @phpstan-return marvin-cli-exit-code
   */
  public function getExitCode() {
    return Utils::getExitCodeBasedOnSeverity(
      $this->getHighestSeverity(),
      $this->getLowestSeverityAsError(),
    );
  }

  public function addEntries(iterable $entries): static {
    foreach ($entries as $entry) {
      $this->entries[$entry->getId()] = $entry;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeEntries(...$entries): static {
    foreach ($entries as $entry) {
      $entryId = $entry instanceof StatusReportEntryInterface ? $entry->getId() : $entry;
      unset($this->entries[$entryId]);
    }

    return $this;
  }

  public function removeAllEntries(): static {
    $this->entries = [];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestSeverity(): ?int {
    $highestSeverity = NULL;
    foreach ($this->entries as $entry) {
      $severity = $entry->getSeverity();
      if ($highestSeverity === NULL || $severity < $highestSeverity) {
        $highestSeverity = $severity;
      }

      if ($highestSeverity === RfcLogLevel::EMERGENCY) {
        break;
      }
    }

    return $highestSeverity;
  }

}
