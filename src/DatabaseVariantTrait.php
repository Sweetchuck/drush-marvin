<?php

declare(strict_types=1);

namespace Drupal\marvin;

trait DatabaseVariantTrait {

  /**
   * @see \Robo\Common\ConfigAwareTrait::getConfig
   */
  abstract public function getConfig();

  protected function getConfigDatabaseVariants(): array {
    $databaseVariants = [];

    $items = (array) $this->getConfig()->get('marvin.database.variant', []);
    foreach ($items as $id => $item) {
      $databaseVariants[$id] = $this->loadDatabaseVariant($id, $item);
    }

    if (!$databaseVariants) {
      $id = 'sqlite';
      $databaseVariants = [
        $id => $this->loadDatabaseVariant($id, ['type' => 'sqlite']),
      ];
    }

    return $databaseVariants;
  }

  protected function loadDatabaseVariant(string $id, array $item): array {
    $item['id'] = $id;

    $item += [
      'type' => 'mysql',
      'enabled' => TRUE,
    ];

    switch ($item['type']) {
      case 'mysql':
      case 'pgsql':
        $item += [
          'binDir' => '/usr/bin',
        ];
        break;

    }

    return $item;
  }

}
