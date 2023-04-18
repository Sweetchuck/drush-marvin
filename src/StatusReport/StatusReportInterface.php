<?php

declare(strict_types=1);

namespace Drupal\marvin\StatusReport;

use Consolidation\AnnotatedCommand\ExitCodeInterface;
use Consolidation\AnnotatedCommand\OutputDataInterface;

/**
 * @template-covariant TKey   of string
 * @template-covariant TValue of \Drupal\marvin\StatusReport\StatusReportEntryInterface
 *
 * @extends \IteratorAggregate<TKey, TValue>
 */
interface StatusReportInterface extends
  \JsonSerializable,
  \IteratorAggregate,
  \Countable,
  OutputDataInterface,
  ExitCodeInterface {

  /**
   * @phpstan-param iterable<\Drupal\marvin\StatusReport\StatusReportEntryInterface> $entries
   */
  public function addEntries(iterable $entries): static;

  /**
   * @phpstan-param string|\Drupal\marvin\StatusReport\StatusReportEntryInterface ...$entries
   */
  public function removeEntries(...$entries): static;

  public function removeAllEntries(): static;

  /**
   * @phpstan-return null|marvin-rfc-log-level
   *   Returns NULL or one of the constants from \Drupal\marvin\RfcLogLevel.
   *
   * @see \Drupal\marvin\RfcLogLevel
   */
  public function getHighestSeverity(): ?int;

}
