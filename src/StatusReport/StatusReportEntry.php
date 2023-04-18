<?php

declare(strict_types=1);

namespace Drupal\marvin\StatusReport;

use Drupal\marvin\RfcLogLevel;

class StatusReportEntry implements StatusReportEntryInterface {

  /**
   * @phpstan-param marvin-status-report-entry-state $properties
   */
  public static function __set_state(array $properties): static {
    /** @phpstan-ignore-next-line */
    $entry = new static();
    if (array_key_exists('id', $properties)) {
      $entry->setId($properties['id']);
    }

    if (array_key_exists('title', $properties)) {
      $entry->setTitle($properties['title']);
    }

    if (array_key_exists('value', $properties)) {
      $entry->setValue($properties['value']);
    }

    if (array_key_exists('description', $properties)) {
      $entry->setDescription($properties['description']);
    }

    if (array_key_exists('severity', $properties)) {
      $entry->setSeverity($properties['severity']);
    }

    return $entry;
  }

  protected string $id = '';

  public function getId(): string {
    return $this->id;
  }

  public function setId(string $id): static {
    $this->id = $id;

    return $this;
  }

  /**
   * @var string
   */
  protected $title = '';

  public function getTitle(): string {
    return $this->title;
  }

  public function setTitle(string $title): static {
    $this->title = $title;

    return $this;
  }

  protected string $value = '';

  /**
   * {@inheritdoc}
   */
  public function getValue(): string {
    return $this->value;
  }

  public function setValue(string $value): static {
    $this->value = $value;

    return $this;
  }

  protected string $description = '';

  public function getDescription(): string {
    return $this->description;
  }

  public function setDescription(string $description): static {
    $this->description = $description;

    return $this;
  }

  /**
   * @phpstan-var marvin-rfc-log-level
   */
  protected int $severity = 0;

  /**
   * {@inheritdoc}
   */
  public function getSeverity(): int {
    return $this->severity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverity(int $severity): static {
    $this->severity = $severity;

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return marvin-status-report-entry-export
   */
  public function jsonSerialize(): array {
    $severityNames = RfcLogLevel::getLevels();
    $severity = $this->getSeverity();

    return [
      'id' => $this->getId(),
      'title' => $this->getTitle(),
      'value' => $this->getValue(),
      'description' => $this->getDescription(),
      'severity' => $severity,
      'severityName' => $severityNames[$severity],
    ];
  }

}
