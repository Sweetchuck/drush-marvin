<?php

declare(strict_types=1);

use Sweetchuck\DrupalDrushHelper\PhpunitBootstrapHelper;
use Symfony\Component\Filesystem\Path;

require_once __DIR__ . '/../web/core/tests/bootstrap.php';

if (class_exists(PhpunitBootstrapHelper::class)) {
  (new PhpunitBootstrapHelper())
    ->populateClassLoader(
      getenv('COMPOSER') ?: 'composer.json',
      NULL,
      Path::makeRelative(
        dirname(__DIR__),
        (string) getcwd(),
      ) ?: '.',
    );
}
