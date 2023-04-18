<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\Attributes as MarvinCLI;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Robo\Collection\CollectionBuilder;

class RuntimeEnvironmentCommands extends CommandsBase {

  /**
   * @phpstan-param array<string, mixed> $options
   */
  #[CLI\Command(name: 'marvin:runtime-environment:list')]
  #[CLI\Help(
    description: 'Lists all the runtime environments.',
  )]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[CLI\Format(
    listDelimiter: ':',
    tableStyle: 'consolidation',
  )]
  #[CLI\DefaultFields(
    fields: [
      'id',
      'description',
      'provider',
    ],
  )]
  #[CLI\FieldLabels(
    labels: [
      'weight' => 'Weight',
      'enabled' => 'Enabled',
      'id' => 'ID',
      'provider' => 'Provider',
      'description' => 'Description',
    ],
  )]
  public function cmdMarvinRuntimeEnvironmentListExecute(
    array $options = [
      'format' => 'yaml',
    ],
  ): CommandResult {
    $exitCode = 0;
    $data = $this->getRuntimeEnvironments();

    return CommandResult::dataWithExitCode($data, $exitCode);
  }

  /**
   * @phpstan-return array<string, marvin-runtime-environment-base>
   */
  #[CLI\Hook(
    type: HookManager::ON_EVENT,
    target: 'marvin:runtime-environment:list',
  )]
  public function onEventMarvinRuntimeEnvironmentList(): array {
    $list = [];
    $root = $this->getProjectRootDir();
    $config = $this->getConfig()->get('marvin.runtime_environments') ?: [];

    if (!empty($config['host']['enabled'])) {
      $list['host'] = array_replace_recursive(
        [
          'weight' => -99,
          // @todo Store the sites somewhere else,
          // because sites aren't change when the runtime_environment changes.
          'description' => 'Uses the host machine without any virtualization',
        ],
        $config['host'],
      );
    }

    if (!empty($config['ddev']['enabled'])
      && file_exists("$root/.ddev/config.yaml")
    ) {
      $list['ddev'] = [
        'description' => 'Runtime environment provided by DDev',
      ];
    }

    return $list;
  }

  #[CLI\Command(name: 'marvin:runtime-environment:switch')]
  #[CLI\Help(
    description: 'Switches to the given runtime environment.',
    synopsis: <<< 'TEXT'
      Usually it means to re-write symlink files.
      TEXT
  )]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[CLI\Argument(
    name: 'rte_id',
    description: 'Runtime environment identifier.'
  )]
  #[MarvinCLI\ValidateRuntimeEnvironmentId(type: 'argument', name: 'rte_id')]
  public function cmdMarvinRuntimeEnvironmentSwitchExecute(string $rte_id): CollectionBuilder {
    $runtimeEnvironments = $this->getRuntimeEnvironments();

    return $this->delegate(
      'runtime-environment:switch',
      $runtimeEnvironments[$rte_id],
    );
  }

}
