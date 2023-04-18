<?php

declare(strict_types=1);

namespace Drupal\marvin;

trait PhpVariantTrait {

  /**
   * @see \Robo\Common\ConfigAwareTrait::getConfig
   */
  abstract public function getConfig();

  protected function getConfigPhpVariants(): array {
    $phpVariants = [];

    $items = (array) $this->getConfig()->get('marvin.php.variant', []);
    foreach ($items as $id => $item) {
      $phpVariants[$id] = $this->loadPhpVariant($id, $item);
    }

    if (!$phpVariants) {
      $item = $this->getPhpVariantFromCurrentPhp();
      $phpVariants = [
        $item['id'] => $item,
      ];
    }

    return $phpVariants;
  }

  protected function loadPhpVariant(string $id, array $item): array {
    $item['id'] = $id;
    $item += [
      'enabled' => TRUE,
      'binDir' => '',
      'phpExecutable' => '',
      'phpdbgExecutable' => '',
      'phpIni' => '',
      'cli' => NULL,
      'version' => [],
    ];

    if ($item['binDir'] && !$item['phpExecutable']) {
      $item['phpExecutable'] = "{$item['binDir']}/php";
    }

    if ($item['binDir'] && !$item['phpdbgExecutable']) {
      $item['phpdbgExecutable'] = "{$item['binDir']}/phpdbg";
    }

    if (!$item['version']) {
      $item['version'] = $this->detectPhpVariantVersion($item);
    }

    return $item;
  }

  protected function createPhpVariantFromCurrent(): array {
    $phpVariant = [
      'id' => PHP_VERSION_ID . '-nts',
      'binDir' => PHP_BINDIR,
    ];

    return $this->loadPhpVariant($phpVariant['id'], $phpVariant);
  }

  protected function detectPhpVariantVersion(array $item): array {
    $parts = [
      'id' => (int) explode('-', $item['id'])[0],
      'major' => 0,
      'minor' => 0,
      'patch' => 0,
    ];

    $matches = [];
    preg_match('/^(?P<major>\d)(?P<minor>\d\d)(?P<patch>\d\d)$/', (string) $parts['id'], $matches);
    if ($matches) {
      $parts['major'] = (int) $matches['major'];
      $parts['minor'] = (int) $matches['minor'];
      $parts['patch'] = (int) $matches['patch'];
    }

    $parts['majorMinor'] = sprintf('%02d%02d', $parts['major'], $parts['minor']);
    $parts['full'] = sprintf('%d.%d.%d', $parts['major'], $parts['minor'], $parts['patch']);

    return $parts;
  }

  protected function getPhpVariantFromCurrentPhp(): array {
    $id = $this->getPhpIdFromCurrentPhp();

    return $this->loadPhpVariant(
      $id,
      [
        'id' => $id,
        'enabled' => TRUE,
        'binDir' => PHP_BINDIR,
        // If the current PHP is pgpdbg then it can cause surprises.
        'phpExecutable' => PHP_BINARY,
        'phpdbgExecutable' => '',
        'phpIni' => '',
        'cli' => NULL,
        'version' => [],
      ]
    );
  }

  protected function getPhpIdFromCurrentPhp(): string {
    return sprintf(
      '%06s-%s',
      (string) PHP_VERSION_ID,
      ZEND_THREAD_SAFE ? 'zts' : 'nts',
    );
  }

}
