<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\Robo\NodeDetectorTaskLoader;
use Robo\Collection\CollectionBuilder;
use Robo\Collection\Tasks as ForEachTaskLoader;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Nvm\NvmTaskLoader;
use Sweetchuck\Robo\Yarn\YarnTaskLoader;
use Symfony\Component\Filesystem\Path;

class NpmCommandsBase extends CommandsBase {

  use ForEachTaskLoader;
  use GitTaskLoader;
  use NvmTaskLoader;
  use YarnTaskLoader;
  use NodeDetectorTaskLoader;

  protected static string $classKeyPrefix = 'marvin.npm';

  protected string $customEventNamePrefix = 'marvin:npm';

  /**
   * @phpstan-param array<string, string> $packages
   *   key: package name.
   *   value: package path.
   */
  protected function getTaskNpmInstallPackages(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->getTaskNpmInstallPackage($packageName, $packagePath));
    }

    return $cb;
  }

  protected function getTaskNpmInstallPackage(string $packageName, string $packagePath): CollectionBuilder {
    $packagePathRelative = Path::makeRelative($packagePath, $this->getProjectRootDir());
    if ($packagePathRelative === '') {
      $packagePathRelative = '.';
    }

    return $this
      ->collectionBuilder()
      ->addTask($this
        ->taskGitListFiles()
        ->setWorkingDirectory($packagePathRelative)
        ->setPaths(['package.json', '*/package.json'])
      )
      ->addTask($this
        ->taskForEach()
        ->iterationMessage('{packagePath}/{key}', ['packagePath' => $packagePathRelative])
        ->deferTaskConfiguration('setIterable', 'files')
        ->withBuilder(function (CollectionBuilder $builder, string $packageJsonFileName) use ($packagePathRelative) {
          $workingDirectory = './' . Path::join($packagePathRelative, Path::getDirectory($packageJsonFileName));

          $builder
            ->addTask($this
              ->taskMarvinNodeDetector()
              ->setWorkingDirectory($workingDirectory)
              ->setRootDirectory($packagePathRelative)
            )
            ->addTask($this
              ->taskYarnInstall()
              ->setWorkingDirectory($workingDirectory)
              ->deferTaskConfiguration('setNodeExecutable', 'nodeDetector.node.executable')
              ->deferTaskConfiguration('setYarnExecutable', 'nodeDetector.yarn.executable')
            );
        })
      );
  }

}
