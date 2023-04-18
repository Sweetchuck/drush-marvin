<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\StatusReport\StatusReportEntry;
use Drupal\marvin\StatusReport\StatusReportInterface;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use Drush\Commands\marvin\StatusReportCommands;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\StatusReportCommands
 * @covers \Drush\Commands\marvin\CommandsBase
 */
class StatusReportCommandsRunTest extends TaskTestBase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesStatusReport(): array {
    $notice1 = StatusReportEntry::__set_state([
      'id' => 'n1',
      'severity' => RfcLogLevel::NOTICE,
    ]);

    $notice2 = StatusReportEntry::__set_state([
      'id' => 'n2',
      'severity' => RfcLogLevel::NOTICE,
    ]);

    $warning1 = StatusReportEntry::__set_state([
      'id' => 'w1',
      'severity' => RfcLogLevel::WARNING,
    ]);

    $error1 = StatusReportEntry::__set_state([
      'id' => 'e1',
      'severity' => RfcLogLevel::ERROR,
    ]);

    return [
      'empty' => [[], []],
      'basic' => [
        [
          $error1->getId() => $error1,
          $notice1->getId() => $notice1,
          $notice2->getId() => $notice2,
          $warning1->getId() => $warning1,
        ],
        [[$notice1], [$warning1, $notice2], [$error1]],
      ],
    ];
  }

  /**
   * @dataProvider casesStatusReport
   *
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $entriesCollection
   * @phpstan-param array<string, mixed> $options
   */
  public function testStatusReport(array $expected, array $entriesCollection, array $options = []): void {
    $hookManager = new HookManager();

    foreach ($entriesCollection as $entries) {
      $hookManager->add(
        $this->createHookCallbackForMarvinStatusReport($entries),
        'on-event',
        'marvin:status-report'
      );
    }

    $logger = new BufferingLogger();

    $commands = new StatusReportCommands();
    $commands->setConfig($this->config);
    $commands->setLogger($logger);
    $commands->setHookManager($hookManager);

    $commandResult = $commands->cmdStatusReportExecute($options);

    /** @phpstan-var \Drupal\marvin\StatusReport\StatusReportInterface<string, \Drupal\marvin\StatusReport\StatusReportEntryInterface> $actualStatusReport */
    $actualStatusReport = $commandResult->getOutputData();

    static::assertInstanceOf(StatusReportInterface::class, $actualStatusReport);
    static::assertSameSize($expected, $actualStatusReport, 'Number of status report entries');

    /** @var string $entryId */
    /** @var \Drupal\marvin\StatusReport\StatusReportEntryInterface $actual */
    foreach ($actualStatusReport as $entryId => $actual) {
      static::assertSame(
        $expected[$entryId]->jsonSerialize(),
        $actual->jsonSerialize(),
        sprintf('%s = %s', $expected[$entryId]->getId(), $actual->getId())
      );
    }
  }

  /**
   * @phpstan-param array<string, mixed> $entries
   */
  protected function createHookCallbackForMarvinStatusReport(array $entries): callable {
    return function () use ($entries): array {
      return $entries;
    };
  }

}
