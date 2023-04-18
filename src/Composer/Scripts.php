<?php

declare(strict_types=1);

namespace Drupal\marvin\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

class Scripts {

  /**
   * @var string[]
   */
  protected static array $drushConfigDirs = [
    'drush',
  ];

  /**
   * @var string[]
   */
  protected static array $drushIncludeDirs = [];

  protected static Event $event;

  /**
   * @var callable
   */
  protected static $processCallbackWrapper;

  public static function postInstallCmd(Event $event): bool {
    static::init($event);

    return static::forwardComposerHookToDrushCommand('post-install-cmd');
  }

  public static function postUpdateCmd(Event $event): bool {
    static::init($event);

    return static::forwardComposerHookToDrushCommand('post-update-cmd');
  }

  /**
   * @todo Probably this whole class is not necessary for "marvin_product",
   * because this can be implemented via
   * composer.json#scripts.post-install-cmd = drush marvin:composer:X.
   */
  protected static function forwardComposerHookToDrushCommand(string $hook): bool {
    $composerConfig = static::$event->getComposer()->getConfig();
    $binDirAbs = $composerConfig->get('bin-dir');
    $binDir = Path::makeRelative($binDirAbs, (string) getcwd());
    $cmd = [
      escapeshellcmd("$binDir/drush"),
    ];

    if (static::$event->getIO()->isDecorated()) {
      $cmd[] = '--ansi';
    }

    foreach (static::getDrushConfigDirs() as $drushConfigDir) {
      $cmd[] = '--config=' . $drushConfigDir;
    }

    foreach (static::getDrushIncludeDirs() as $drushIncludeDir) {
      $cmd[] = '--include=' . $drushIncludeDir;
    }

    $cmd[] = "marvin:composer:$hook";

    if (static::$event->isDevMode()) {
      $cmd[] = '--dev-mode';
    }

    $process = new Process($cmd);

    $exitCode = $process->run(static::$processCallbackWrapper);
    if ($exitCode === 0) {
      return TRUE;
    }

    static::$event->getIO()->writeError($process->getErrorOutput());

    return FALSE;
  }

  protected static function init(Event $event): void {
    static::$event = $event;
    static::$processCallbackWrapper = function (string $type, string $buffer) {
      static::processCallback($type, $buffer);
    };
  }

  /**
   * @return string[]
   */
  protected static function getDrushConfigDirs(): array {
    // @todo Dynamically detect the install path from composer.json.
    return static::$drushConfigDirs;
  }

  /**
   * @return string[]
   */
  protected static function getDrushIncludeDirs(): array {
    // @todo Dynamically detect the install path from composer.json.
    return static::$drushIncludeDirs;
  }

  protected static function getComposerBinDir(): string {
    return static::$event->getComposer()->getConfig()->get('bin-dir');
  }

  protected static function processCallback(string $type, string $buffer): void {
    if ($type === Process::OUT) {
      static::$event->getIO()->write($buffer);

      return;
    }

    static::$event->getIO()->writeError($buffer);
  }

}
