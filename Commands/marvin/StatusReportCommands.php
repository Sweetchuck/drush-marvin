<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\StatusReport\StatusReport;
use Drupal\marvin\StatusReport\StatusReportInterface;
use Drupal\marvin\Utils as MarvinUtils;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;

class StatusReportCommands extends CommandsBase {

  protected static string $classKeyPrefix = 'marvin.statusReport';

  protected string $customEventNamePrefix = 'marvin:status-report';

  /**
   * @phpstan-param array<string, mixed> $options
   */
  #[CLI\Command(name: 'marvin:status-report')]
  #[CLI\Help(
    description: 'Displays Marvin related status report entries.',
  )]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[CLI\Format(
    listDelimiter: ':',
    tableStyle: 'compact',
  )]
  #[CLI\DefaultFields(
    fields: [
      'severityName',
      'title',
      'value',
      'description',
    ],
  )]
  #[CLI\FieldLabels(
    labels: [
      'id' => 'ID',
      'title' => 'Title',
      'value' => 'Value',
      'description' => 'Description',
      'severity' => 'Severity ID',
      'severityName' => 'Severity',
    ],
  )]
  public function cmdMarvinStatusReportExecute(
    array $options = [
      'format' => 'yaml',
    ],
  ): CommandResult {
    $statusReport = (new StatusReport())->addEntries($this->collectStatusReportEntries());

    return CommandResult::dataWithExitCode($statusReport, $statusReport->getExitCode());
  }

  #[CLI\Hook(
    type: HookManager::ALTER_RESULT,
    target: 'marvin:status-report',
  )]
  public function cmdMarvinStatusReportAlter(CommandResult $result, CommandData $commandData): void {
    $statusReport = $result->getOutputData();
    if ($statusReport instanceof StatusReportInterface) {
      $expectedFormat = $commandData->input()->getOption('format');
      $statusReport = match ($expectedFormat) {
        'table' => MarvinUtils::convertStatusReportToRowsOfFields($statusReport),
        default => $statusReport->jsonSerialize(),
      };

      $result->setOutputData($statusReport);
    }
  }

  /**
   * @return \Drupal\marvin\StatusReport\StatusReportEntryInterface[]
   */
  protected function collectStatusReportEntries(): array {
    $eventName = $this->getCustomEventName('');
    $this->getLogger()->debug(
      'event trigger: {eventName}',
      [
        'eventName' => $eventName,
      ],
    );

    $customEventHandlers = $this->getCustomEventHandlers($eventName);
    $entries = [];
    foreach ($customEventHandlers as $customEventHandler) {
      /** @var \Drupal\marvin\StatusReport\StatusReportEntryInterface[] $result */
      $result = $customEventHandler($this->input(), $this->output());
      foreach ($result as $entry) {
        $entries[$entry->getId()] = $entry;
      }
    }

    ksort($entries);

    return $entries;
  }

}
