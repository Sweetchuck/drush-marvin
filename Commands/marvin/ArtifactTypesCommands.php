<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;

class ArtifactTypesCommands extends ArtifactCommandsBase {

  /**
   * @phpstan-var array<string, marvin-artifact-type>
   */
  protected array $types = [];

  /**
   * @phpstan-param array<string, mixed> $options
   *
   * @phpstan-return array<string, marvin-artifact-type>
   *
   * @todo Change return type to CommandResult.
   */
  #[CLI\Command(name: 'marvin:artifact:types')]
  #[CLI\Help(
    description: 'Lists all available artifact types.'
  )]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[CLI\Format(
    listDelimiter: ':',
    tableStyle: 'compact',
  )]
  #[CLI\DefaultFields(
    fields: [
      'id',
      'label',
      'description',
    ],
  )]
  #[CLI\FieldLabels(
    labels: [
      'id' => 'ID',
      'label' => 'Label',
      'description' => 'Description',
      'weight' => 'Weight',
    ],
  )]
  public function cmdMarvinArtifactTypesExecute(
    array $options = [
      'format' => 'yaml',
    ],
  ): array {
    return $this
      ->collectArtifactTypes()
      ->expandArtifactTypes()
      ->sortArtifactTypes()
      ->types;
  }

  #[CLI\Hook(
    type: HookManager::ALTER_RESULT,
    target: 'marvin:artifact:types',
  )]
  public function cmdMarvinArtifactTypesAlter(mixed $result, CommandData $commandData): mixed {
    $expectedFormat = $commandData->input()->getOption('format');
    if ($expectedFormat === 'table' && is_array($result)) {
      return $this->convertArtifactTypesToRowsOfFields($result);
    }

    return $result;
  }

  protected function collectArtifactTypes(): static {
    $projectType = $this->getConfig()->get('marvin.projectType');

    /** @var callable[] $eventHandlers */
    $eventHandlers = $this->getCustomEventHandlers($this->getCustomEventName('types'));
    $this->types = [];
    foreach ($eventHandlers as $eventHandler) {
      $this->types += $eventHandler($projectType);
    }

    return $this;
  }

  protected function expandArtifactTypes(): static {
    foreach ($this->types as $id => &$info) {
      $info['id'] = $id;
      $info += ['weight' => 0];
    }

    return $this;
  }

  protected function sortArtifactTypes(): static {
    uasort($this->types, $this->getArtifactTypesComparer());

    return $this;
  }

  protected function getArtifactTypesComparer(): callable {
    $comparer = new ArrayValueComparer();
    $comparer->setOptions($this->getArtifactTypesComparerOptions());

    return $comparer;
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  protected function getArtifactTypesComparerOptions(): array {
    return [
      'weight' => [
        'default' => 0,
      ],
      'label' => [
        'default' => '',
      ],
      'id' => [
        'default' => '',
      ],
    ];
  }

  /**
   * @phpstan-param array<string, marvin-artifact-type> $artifactTypes
   */
  protected function convertArtifactTypesToRowsOfFields(array $artifactTypes): RowsOfFields {
    return new RowsOfFields($artifactTypes);
  }

}
