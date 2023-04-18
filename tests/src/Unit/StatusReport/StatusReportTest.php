<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\StatusReport;

use Drupal\marvin\StatusReport\StatusReport;
use Drupal\marvin\StatusReport\StatusReportEntry;
use PHPUnit\Framework\TestCase;

/**
 * @group marvin
 *
 * @covers \Drupal\marvin\StatusReport\StatusReport
 */
class StatusReportTest extends TestCase {

  public function testAllInOne(): void {
    $expected = [
      'a' => [
        'id' => 'a',
        'title' => 'a-t',
        'value' => 'a-v',
        'description' => 'a-d',
        'severity' => 5,
        'severityName' => 'Notice',
      ],
      'b' => [
        'id' => 'b',
        'title' => 'b-t',
        'value' => 'b-v',
        'description' => 'b-d',
        'severity' => 4,
        'severityName' => 'Warning',
      ],
      'c' => [
        'id' => 'c',
        'title' => 'c-t',
        'value' => 'c-v',
        'description' => 'c-d',
        'severity' => 3,
        'severityName' => 'Error',
      ],
      'd' => [
        'id' => 'd',
        'title' => 'd-t',
        'value' => 'd-v',
        'description' => 'd-d',
        'severity' => 2,
        'severityName' => 'Critical',
      ],
    ];

    $entryA = StatusReportEntry::__set_state($expected['a']);
    $entryB = StatusReportEntry::__set_state($expected['b']);
    $entryC = StatusReportEntry::__set_state($expected['c']);
    $entryD = StatusReportEntry::__set_state($expected['d']);

    $statusReport = new StatusReport();
    static::assertSame([], $statusReport->jsonSerialize());
    static::assertSame(NULL, $statusReport->getHighestSeverity());
    static::assertSame(0, $statusReport->count());

    $statusReport->addEntries([$entryA, $entryB, $entryC, $entryD]);
    /** @var \Drupal\marvin\StatusReport\StatusReportEntryInterface $entry */
    foreach ($statusReport as $entryId => $entry) {
      static::assertSame($expected[$entryId], $entry->jsonSerialize());
    }

    static::assertSame($expected, $statusReport->jsonSerialize());
    static::assertSame($expected, $statusReport->getOutputData());
    static::assertSame(4, $statusReport->count());
    static::assertSame(3, $statusReport->getExitCode());
    static::assertSame(2, $statusReport->getHighestSeverity());

    $statusReport->removeEntries('b');
    unset($expected['b']);
    static::assertSame($expected, $statusReport->jsonSerialize());
    static::assertSame(3, $statusReport->count());
    static::assertSame(3, $statusReport->getExitCode());
    static::assertSame(2, $statusReport->getHighestSeverity());

    $statusReport->removeEntries($entryC, 'd');
    unset($expected['c'], $expected['d']);
    static::assertSame($expected, $statusReport->jsonSerialize());
    static::assertSame(1, $statusReport->count());
    static::assertSame(0, $statusReport->getExitCode());
    static::assertSame(5, $statusReport->getHighestSeverity());

    $statusReport->removeAllEntries();
    static::assertSame([], $statusReport->jsonSerialize());
    static::assertSame(0, $statusReport->count());
    static::assertSame(0, $statusReport->getExitCode());
    static::assertSame(NULL, $statusReport->getHighestSeverity());
  }

}
