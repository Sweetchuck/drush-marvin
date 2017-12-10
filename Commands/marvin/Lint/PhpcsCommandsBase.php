<?php

namespace Drush\Commands\marvin\Lint;

use Drush\marvin\ComposerInfo;
use Drush\marvin\Robo\PhpcsConfigFallbackTaskLoader;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Webmozart\PathUtil\Path;

class PhpcsCommandsBase extends CommandsBase {

  use PhpcsTaskLoader;
  use PhpcsConfigFallbackTaskLoader;
  use GitTaskLoader;

  /**
   * @return null|\Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskLintPhpcsExtension(string $workingDirectory): ?TaskInterface {
    $gitHook = $this->getConfig()->get('command.marvin.settings.gitHook');
    $phpcsXml = $this->getPhpcsConfigurationFileName($workingDirectory);

    $presetName = $this->getPresetNameByEnvironmentVariant();
    $options = $this->getConfigValue("preset.$presetName");
    if ($phpcsXml) {
      unset($options['standards']);
    }

    $options['phpcsExecutable'] = Path::join(
      $this->makeRelativePathToComposerBinDir($workingDirectory),
      'phpcs'
    );
    $options['workingDirectory'] = $workingDirectory;
    $options += ['lintReporters' => []];
    $options['lintReporters'] += $this->getLintReporters();

    if ($gitHook === 'pre-commit') {
      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskPhpcsParseXml()
          ->setWorkingDirectory($workingDirectory)
          ->setFailOnXmlFileNotExists(FALSE)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskMarvinPhpcsConfigFallback()
          ->setWorkingDirectory($workingDirectory)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskGitReadStagedFiles()
          ->setCommandOnly(TRUE)
          ->deferTaskConfiguration('setPaths', 'phpcsXml.files'))
        ->addTask($this
          ->taskPhpcsLintInput($options)
          ->deferTaskConfiguration('setFiles', 'files')
          ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
    }

    if (!$phpcsXml) {
      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskMarvinPhpcsConfigFallback()
          ->setWorkingDirectory($workingDirectory)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskPhpcsLintFiles($options)
          ->deferTaskConfiguration('setFiles', 'phpcsXml.files')
          ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
    }

    return $this->taskPhpcsLintFiles($options);
  }

  protected function getPhpcsConfigurationFileName(string $directory): string {
    $directory = $directory ?? '.';
    $candidates = [
      'phpcs.xml',
      'phpcs.xml.dist',
    ];

    foreach ($candidates as $candidate) {
      $fileName = Path::join($directory, $candidate);
      if (file_exists($fileName)) {
        return $fileName;
      }
    }

    return '';
  }

  protected function getTaskPhpcsConfigSetInstalledPaths(string $workingDirectory): TaskInterface {
    $composerInfo = ComposerInfo::create();
    $binDir = $composerInfo['config']['bin-dir'];
    $vendorDir = $composerInfo['config']['vendor-dir'];
    $vendorDirAbs = Path::makeAbsolute($vendorDir, $workingDirectory);
    $cmdPattern = '%s --config-set installed_paths %s';
    $cmdArgs = [
      escapeshellcmd("$binDir/phpcs"),
      escapeshellarg("$vendorDirAbs/drupal/coder/coder_sniffer"),
    ];

    return $this->taskExec(vsprintf($cmdPattern, $cmdArgs));
  }

}