<?php

declare(strict_types=1);

namespace Drupal\marvin\StatusReport;

interface StatusReportEntryInterface extends \JsonSerializable {

  public function getId(): string;

  public function setId(string $id): static;

  public function getTitle(): string;

  public function setTitle(string $title): static;

  public function getValue(): string;

  public function setValue(string $value): static;

  public function getDescription(): string;

  public function setDescription(string $description): static;

  /**
   * @phpstan-return marvin-rfc-log-level
   *
   * @see \Drupal\Core\Logger\RfcLogLevel::getLevels
   */
  public function getSeverity(): int;

  /**
   * @phpstan-param marvin-rfc-log-level $severity
   *
   * @see \Drupal\Core\Logger\RfcLogLevel::getLevels
   */
  public function setSeverity(int $severity): static;

}
