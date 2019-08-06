<?php

namespace Drush\Commands\marvin;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Robo\VersionNumberTaskLoader;
use Drupal\marvin\Utils as MarvinUtils;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

abstract class ArtifactBuildCommandsBase extends ArtifactCommandsBase {

  use GitTaskLoader;
  use VersionNumberTaskLoader;

  /**
   * {@inheritdoc}
   */
  protected $customEventNamePrefix = 'marvin:artifact:build';

  /**
   * @var string
   *
   * @abstract
   */
  protected $artifactType = '';

  /**
   * @var string[]
   *
   * @todo This should be available from everywhere.
   */
  protected $versionPartNames = [
    'major',
    'minor',
    'patch',
    'pre-release',
    'meta-data',
  ];

  /**
   * @var string
   */
  protected $defaultVersionPartToBump = 'minor';

  /**
   * @var string
   */
  protected $srcDir = '.';

  /**
   * @var string
   */
  protected $artifactDir = '';

  /**
   * @var string
   */
  protected $versionPartToBump = '';

  /**
   * @var int
   */
  protected $highestBuildStepWeight = -250;

  /**
   * @var string
   */
  protected $versionTagNamePattern = '/^(v){0,1}(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)(|-(?P<special>[\da-zA-Z.]+))(|\+(?P<metadata>[\da-zA-Z.]+))$/';

  abstract protected function isApplicable(string $projectType): bool;

  protected function getBuildSteps(): array {
    $this->highestBuildStepWeight = -250;

    return [
      'initStateData.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskInitStateData(),
      ],
      'detectLatestVersionNumber.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskDetectLatestVersionNumber(),
      ],
      'composeNextVersionNumber.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskComposeNextVersionNumber(),
      ],
      'composeBuildDirPath.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskComposeBuildDir(),
      ],
      'prepareDirectory.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskPrepareDirectory(),
      ],
      'copyFilesCollect.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskCopyFilesCollect(),
      ],
      'copyFiles.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskCopyFiles(),
      ],
      'bumpVersionNumber.root.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskBumpVersionNumberRoot(),
      ],
      'collectCustomExtensionDirs.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskCollectChildExtensionDirs(),
      ],
      'bumpVersionNumber.extensions.marvin' => [
        'weight' => $this->incrementBuildStepWeight(),
        'task' => $this->getTaskBumpVersionNumberExtensions('customExtensionDirs', 'nextVersionNumber.drupal'),
      ],
      'cleanupFilesCollect.marvin' => [
        'weight' => $this->highestBuildStepWeight + 9990,
        'task' => $this->getTaskCleanupCollect(),
      ],
      'cleanupFiles.marvin' => [
        'weight' => $this->highestBuildStepWeight + 9999,
        'task' => $this->getTaskCleanup(),
      ],
    ];
  }

  protected function getInitialStateData(): array {
    return [
      'coreVersion' => '8.x',
      'artifactType' => $this->artifactType,
      'versionPartToBump' => $this->versionPartToBump,
      'composerInfo' => ComposerInfo::create($this->srcDir),
      'filesToCleanup' => [],
    ];
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskInitStateData() {
    return function (RoboStateData $data): int {
      $data->mergeData($this->getInitialStateData());

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskDetectLatestVersionNumber() {
    return function (RoboStateData $data): int {
      $logger = $this->getLogger();
      $logContext = [
        'taskName' => 'DetectLatestVersionNumber',
      ];

      $logger->notice('{taskName}', $logContext);

      $result = $this
        ->taskGitTagList()
        ->setWorkingDirectory($this->srcDir)
        ->setMergedState(TRUE)
        ->run();

      if (!$result->wasSuccessful()) {
        return 0;
      }

      $tagNames = array_keys($result['gitTags'] ?? []);
      $tagNames = array_filter($tagNames, $this->getVersionTagNameFilter());
      usort($tagNames, $this->getVersionTagNameComparer());

      $tag = end($tagNames);
      if ($tag) {
        $data['latestVersionNumber.semver'] = $tag;
      }

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskComposeNextVersionNumber() {
    return function (RoboStateData $data): int {
      $logger = $this->getLogger();
      $logContext = [
        'taskName' => 'ComposeNextVersionNumber',
      ];

      $logger->notice('{taskName}', $logContext);


      $data['nextVersionNumber.semver'] = NULL;
      $data['nextVersionNumber.drupal'] = NULL;

      $versionPartToBump = $data['versionPartToBump'] ?? $this->defaultVersionPartToBump;
      if (!in_array($versionPartToBump, $this->versionPartNames)) {
        $data['nextVersionNumber.semver'] = $versionPartToBump;
      }

      if (!$data['nextVersionNumber.semver']) {
        $data['nextVersionNumber.semver'] = (string) MarvinUtils::incrementSemVersion(
          $data['latestVersionNumber.semver'] ?? '0.0.0',
          $versionPartToBump
        );
      }

      if ($data['nextVersionNumber.semver']) {
        $data['nextVersionNumber.drupal'] = MarvinUtils::semverToDrupal(
          $data['coreVersion'],
          $data['nextVersionNumber.semver']
        );
      }

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskComposeBuildDir() {
    return function (RoboStateData $data): int {
      $logger = $this->getLogger();
      $logContext = [
        'taskName' => 'ComposeBuildDir',
      ];

      $logger->notice('{taskName}', $logContext);

      $data['buildDir'] = "{$this->artifactDir}/{$data['nextVersionNumber.semver']}/{$data['artifactType']}";

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskPrepareDirectory() {
    return $this
      ->taskMarvinPrepareDirectory()
      ->deferTaskConfiguration('setWorkingDirectory', 'buildDir');
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskCopyFilesCollect() {
    return $this
      ->taskMarvinArtifactCollectFiles()
      ->setPackagePath($this->srcDir);
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskCopyFiles() {
    return $this
      ->taskMarvinCopyFiles()
      ->setSrcDir($this->srcDir)
      ->deferTaskConfiguration('setDstDir', 'buildDir')
      ->deferTaskConfiguration('setFiles', 'files');
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskBumpVersionNumberRoot() {
    return $this
      ->taskMarvinVersionNumberBumpExtensionInfo()
      ->setBumpExtensionInfo(FALSE)
      ->deferTaskConfiguration('setPackagePath', 'buildDir')
      ->deferTaskConfiguration('setVersionNumber', 'nextVersionNumber.drupal');
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  abstract protected function getTaskCollectChildExtensionDirs();

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskBumpVersionNumberExtensions(
    string $iterableStateKey,
    string $versionStateKey
  ) {
    $forEachTask = $this->taskForEach();

    $forEachTask
      ->deferTaskConfiguration('setIterable', $iterableStateKey)
      ->withBuilder(function (
        CollectionBuilder $builder,
        $key,
        string $extensionDir
      ) use (
        $forEachTask,
        $versionStateKey
      ) {
        if (!file_exists($extensionDir)) {
          return;
        }

        $builder->addTask(
          $this
            ->taskMarvinVersionNumberBumpExtensionInfo()
            ->setBumpComposerJson(FALSE)
            ->setPackagePath($extensionDir)
            ->setVersionNumber($forEachTask->getState()->offsetGet($versionStateKey))
        );
      });

    return $forEachTask;
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskCleanupCollect() {
    return function (RoboStateData $data): int {
      $buildDir = $data['buildDir'];

      $data['filesToCleanup'][] = Path::join($buildDir, 'patches');

      /** @var \Symfony\Component\Finder\SplFileInfo[] $gitDirs */
      $gitDirs =  (new Finder())
        ->in($buildDir)
        ->depth('> 0')
        ->ignoreDotFiles(false)
        ->ignoreVCS(false)
        ->name('.git');
      foreach ($gitDirs as $gitDir) {
        $data['filesToCleanup'][] = $gitDir->getPathname();
      }

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskCleanup() {
    return $this
      ->taskFilesystemStack()
      ->deferTaskConfiguration('remove', 'filesToCleanup');
  }

  protected function getVersionTagNameFilter(): callable {
    return function ($version): bool {
      return preg_match($this->versionTagNamePattern, (string) $version) === 1;
    };
  }

  protected function getVersionTagNameComparer(): callable {
    return 'version_compare';
  }

  protected function incrementBuildStepWeight(): int {
    $this->highestBuildStepWeight += 10;

    return $this->highestBuildStepWeight;
  }

}
