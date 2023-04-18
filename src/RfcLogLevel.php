<?php

declare(strict_types=1);

namespace Drupal\marvin;

/**
 * RfcLogLevel from Drupal core is not always available.
 *
 * @see \Psr\Log\LogLevel
 * @see \Drupal\Core\Logger\RfcLogLevel
 * @see http://tools.ietf.org/html/rfc5424
 *
 * @todo Use enum.
 */
class RfcLogLevel {

  /**
   * Log message severity -- Emergency: system is unusable.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const EMERGENCY = 0;

  /**
   * Log message severity -- Alert: action must be taken immediately.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const ALERT = 1;

  /**
   * Log message severity -- Critical conditions.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const CRITICAL = 2;

  /**
   * Log message severity -- Error conditions.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const ERROR = 3;

  /**
   * Log message severity -- Warning conditions.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const WARNING = 4;

  /**
   * Log message severity -- Normal but significant conditions.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const NOTICE = 5;

  /**
   * Log message severity -- Informational messages.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const INFO = 6;

  /**
   * Log message severity -- Debug-level messages.
   *
   * @phpstan-var marvin-rfc-log-level
   */
  const DEBUG = 7;

  /**
   * An array with the severity levels as keys and labels as values.
   *
   * @phpstan-var array<marvin-rfc-log-level, string>
   */
  protected static array $levels = [];

  /**
   * Returns a list of severity levels, as defined in RFC 5424.
   *
   * @phpstan-return array<marvin-rfc-log-level, string>
   */
  public static function getLevels(): array {
    if (!static::$levels) {
      static::$levels = [
        static::EMERGENCY => 'Emergency',
        static::ALERT => 'Alert',
        static::CRITICAL => 'Critical',
        static::ERROR => 'Error',
        static::WARNING => 'Warning',
        static::NOTICE => 'Notice',
        static::INFO => 'Info',
        static::DEBUG => 'Debug',
      ];
    }

    return static::$levels;
  }

}
