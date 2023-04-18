<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\Utils;
use Drush\Attributes as CLI;
use Sweetchuck\LintReport\Reporter\BaseReporter;

class LintHooksCommands extends CommandsBase {

  public const TAG_PRE_COMMAND_MARVIN_INIT_LINT_REPORTERS = 'pre-command-marvin-init-lint-reporters';

  #[CLI\Hook(
    type: HookManager::PRE_COMMAND_HOOK,
    selector: self::TAG_PRE_COMMAND_MARVIN_INIT_LINT_REPORTERS,
  )]
  public function onHookPreCommandMarvinInitLintReporters(): void {
    $this->getLogger()->debug('triggered hook: pre-command @marvinInitLintReporters');

    Utils::initLintReporters(
      BaseReporter::getServices(),
      $this->getContainer(),
    );
  }

}
