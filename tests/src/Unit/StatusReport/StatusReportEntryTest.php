<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\StatusReport;

use Drupal\marvin\StatusReport\StatusReportEntry;
use PHPUnit\Framework\TestCase;

/**
 * @group marvin
 *
 * @covers \Drupal\marvin\StatusReport\StatusReportEntry
 */
class StatusReportEntryTest extends TestCase {

  public function testAllInOne():void {
    $expected = [
      'id' => 'i',
      'title' => 't',
      'value' => 'v',
      'description' => 'd',
      'severity' => 5,
      'severityName' => 'Notice',
    ];

    $entry = (new StatusReportEntry())
      ->setId('i')
      ->setTitle('t')
      ->setValue('v')
      ->setDescription('d')
      ->setSeverity(5);

    static::assertSame($expected, $entry->jsonSerialize());

    $entry = StatusReportEntry::__set_state($expected);
    static::assertSame($expected, $entry->jsonSerialize());
  }

}
