<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\String\UnicodeString;

/**
 * @template TKey   of string
 * @template TValue of mixed
 *
 * @implements \ArrayAccess<TKey, TValue>
 *
 * @property-read string $name
 * @property-read string $packageVendor
 * @property-read string $packageName
 *
 * @todo A "write" or a "dump" method would be handy.
 */
class ComposerInfo implements \ArrayAccess {

  /**
   * @var static[]
   */
  protected static array $instances = [];

  protected Filesystem $fs;

  /**
   * @phpstan-var marvin-composer-info
   *
   * @phpstan-ignore-next-line
   */
  protected array $json = [];

  protected string $jsonFileName = '';

  public function getJsonFileName(): string {
    return $this->jsonFileName;
  }

  public function getWorkingDirectory(): string {
    return Path::getDirectory($this->getJsonFileName());
  }

  protected int $jsonChangedTime = 0;

  /**
   * @phpstan-var marvin-composer-lock
   *
   * @phpstan-ignore-next-line
   */
  protected array $lock = [];

  protected string $lockFileName = '';

  public function getLockFileName(): string {
    return $this->lockFileName;
  }

  protected int $lockChangedTime = 0;

  /**
   * @phpstan-var array<string, mixed>
   */
  protected array $jsonDefault = [
    'type' => 'library',
    'config' => [
      'bin-dir' => 'vendor/bin',
      'vendor-dir' => 'vendor',
    ],
  ];

  public static function create(string $baseDir = '', string $jsonFileName = '', ?Filesystem $fs = NULL): static {
    if (!$jsonFileName) {
      $jsonFileName = Utils::getComposerJsonFileName();
    }

    // @todo Avoid using \getcwd() here.
    $instanceId = Path::isAbsolute($jsonFileName) ?
      $jsonFileName
      : Path::join(
        ($baseDir ?: (string) getcwd()),
        $jsonFileName,
      );

    if (!isset(static::$instances[$instanceId])) {
      /* @phpstan-ignore-next-line */
      static::$instances[$instanceId] = new static($jsonFileName, $fs, $baseDir);
    }

    return static::$instances[$instanceId];
  }

  protected function __construct(string $jsonFileName, ?Filesystem $fs = NULL, string $baseDir = '') {
    $this->fs = $fs ?: new Filesystem();
    $this->jsonFileName = Path::isAbsolute($jsonFileName) ?
      $jsonFileName
      : Path::join(($baseDir ?: (string) getcwd()), $jsonFileName);
    $this->initLockFileName();
  }

  public function __destruct() {
    unset(static::$instances[$this->jsonFileName]);
  }

  protected function initLockFileName(): static {
    $jsonExtension = pathinfo($this->jsonFileName, PATHINFO_EXTENSION);
    $jsonExtensionLength = mb_strlen($jsonExtension);
    $this->lockFileName = mb_substr($this->jsonFileName, 0, $jsonExtensionLength * -1) . 'lock';

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset): bool {
    return array_key_exists($offset, $this->getJson());
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset): mixed {
    $this->initJson();

    /* @phpstan-ignore-next-line */
    return $this->json[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value): void {
    $this->initJson();
    /* @phpstan-ignore-next-line */
    $this->json[$offset] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset): void {
    $this->initJson();
    unset($this->json[$offset]);
  }

  public function __get(string $name): mixed {
    $this->initJson();

    if (array_key_exists($name, $this->json)) {
      return $this->json[$name];
    }

    switch ($name) {
      case 'name':
        return $this->json['name'] ?? NULL;

      case 'packageVendor':
      case 'packageName':
        if (!isset($this->json['name'])) {
          return NULL;
        }

        [$packageVendor, $packageName] = explode('/', $this->json['name']) + [1 => ''];

        return $name === 'packageVendor' ? $packageVendor : $packageName;
    }

    $pattern = 'Undefined property via __get(): "%s" in "%s" on line "%d"';
    $trace = debug_backtrace();
    trigger_error(
      sprintf(
        $pattern,
        $name,
        $trace[0]['file'] ?? 'unknown',
        $trace[0]['line'] ?? 'unknown',
      ),
      \E_NOTICE,
    );

    /* @phpstan-ignore-next-line */
    return NULL;
  }

  protected function initJson(): static {
    $this->checkJsonExists();
    $changedTime = filectime($this->jsonFileName);
    if ($changedTime !== FALSE && $changedTime > $this->jsonChangedTime) {
      /* @phpstan-ignore-next-line */
      $this->json = array_replace_recursive(
        $this->jsonDefault,
        json_decode(
          file_get_contents($this->jsonFileName) ?: '{}',
          TRUE,
        ),
      );

      $this->jsonChangedTime = $changedTime;
    }

    return $this;
  }

  protected function initLock(): static {
    return $this
      ->initLockReadFile()
      ->initLockChangeKeys();
  }

  protected function initLockReadFile(): static {
    if (!$this->fs->exists($this->lockFileName)) {
      $this->lock = [
        'content-hash' => '',
        'packages' => [],
        'packages-dev' => [],
        'aliases' => [],
        'minimum-stability' => [],
        'stability-flags' => [],
        'prefer-stable' => TRUE,
        'prefer-lowest' => FALSE,
        'platform' => [],
        'platform-dev' => [],
        'plugin-api-version' => '',
      ];

      return $this;
    }

    $changedTime = filectime($this->lockFileName);
    if ($changedTime !== FALSE && $changedTime > $this->lockChangedTime) {
      $this->lock = json_decode(
        file_get_contents($this->lockFileName) ?: '{}',
        TRUE,
      );
      $this->lockChangedTime = $changedTime;
    }

    return $this;
  }

  protected function initLockChangeKeys(): static {
    foreach (['packages', 'packages-dev'] as $mainKey) {
      if (!isset($this->lock[$mainKey])) {
        continue;
      }

      foreach ($this->lock[$mainKey] as $key => $package) {
        unset($this->lock[$mainKey][$key]);
        $this->lock[$mainKey][$package['name']] = $package;
      }
    }

    return $this;
  }

  public function invalidate(): static {
    $this->jsonChangedTime = 0;
    $this->lockChangedTime = 0;

    return $this;
  }

  /**
   * @phpstan-return marvin-composer-info
   */
  public function getJson(): array {
    return $this
      ->initJson()
      ->json;
  }

  /**
   * @phpstan-return marvin-composer-lock
   */
  public function getLock(): array {
    return $this
      ->initLock()
      ->lock;
  }

  public function getDrupalExtensionInstallDir(string $type): ?string {
    $type = (new UnicodeString($type))
      ->ensureStart('drupal-')
      ->toString();
    $json = $this->getJson();
    $installerPaths = $json['extra']['installer-paths'] ?? [];

    foreach ($installerPaths as $dir => $conditions) {
      if (in_array("type:$type", $conditions)) {
        return $dir;
      }
    }

    return NULL;
  }

  public function getDrupalRootDir(): string {
    $installerPaths = $this['extra']['installer-paths'] ?? [];
    foreach ($installerPaths as $installDir => $rules) {
      if (in_array('drupal/core', $rules) || in_array('type:drupal-core', $rules)) {
        $installDir = strtr(
          $installDir,
          [
            '{$name}' => 'core',
          ]
        );

        return dirname($installDir);
      }
    }

    return $this['config']['vendor-dir'] . '/drupal';
  }

  protected function checkJsonExists(): static {
    if (!$this->fs->exists($this->jsonFileName)) {
      throw new FileNotFoundException(NULL, 1, NULL, $this->jsonFileName);
    }

    return $this;
  }

}
