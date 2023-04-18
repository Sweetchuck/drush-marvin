<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit;

use Consolidation\AnnotatedCommand\CommandError;
use Drupal\marvin\ComposerInfo;
use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\StatusReport\StatusReport;
use Drupal\marvin\StatusReport\StatusReportEntry;
use Drupal\marvin\Utils;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

/**
 * @group marvin
 *
 * @covers \Drupal\marvin\Utils
 */
class UtilsTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    putenv('COMPOSER');
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesIsDrupalPackage(): array {
    return [
      'module' => [TRUE, ['type' => 'drupal-module']],
      'theme' => [TRUE, ['type' => 'drupal-theme']],
      'profile' => [TRUE, ['type' => 'drupal-profile']],
      'drush' => [TRUE, ['type' => 'drupal-drush']],
      'core' => [TRUE, ['type' => 'drupal-core']],
      'library' => [FALSE, ['type' => 'library']],
    ];
  }

  /**
   * @dataProvider casesIsDrupalPackage
   *
   * @phpstan-param marvin-composer-info $package
   */
  public function testIsDrupalPackage(bool $expected, array $package): void {
    static::assertSame($expected, Utils::isDrupalPackage($package));
  }

  /**
   * @backupGlobals
   */
  public function testGetComposerJsonFileName(): void {
    static::assertSame('composer.json', Utils::getComposerJsonFileName());

    putenv('COMPOSER=foo.json');
    static::assertSame('foo.json', Utils::getComposerJsonFileName());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesIsValidDrupalExtensionVersionNumber(): array {
    return [
      '8.x-1.0' => [TRUE, '8.x-1.0'],
    ];
  }

  /**
   * @dataProvider casesIsValidDrupalExtensionVersionNumber
   */
  public function testIsValidDrupalExtensionVersionNumber(bool $expected, string $versionNumber): void {
    static::assertSame($expected, Utils::isValidDrupalExtensionVersionNumber($versionNumber));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesDrupalPhpExtensionPatterns(): array {
    return [
      'basic' => [
        [
          '*.engine',
          '*.install',
          '*.module',
          '*.php',
          '*.profile',
          '*.theme',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesDrupalPhpExtensionPatterns
   */
  public function testDrupalPhpExtensionPatterns(mixed $expected): void {
    static::assertSame($expected, Utils::drupalPhpExtensionPatterns());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesFindFileUpward(): array {
    return [
      'not-exists' => [
        '',
        'a.txt',
        'foo',
        [
          'foo' => [],
        ],
      ],
      '0-0' => [
        'vfs://testFindFileUpward',
        'a.txt',
        '.',
        [
          'a.txt' => 'okay',
        ],
      ],
      '1-0' => [
        'vfs://testFindFileUpward',
        'a.txt',
        'foo',
        [
          'a.txt' => 'okay',
          'foo' => [],
        ],
      ],
      '2-0' => [
        'vfs://testFindFileUpward',
        'a.txt',
        'foo/bar',
        [
          'a.txt' => 'okay',
          'foo' => [
            'bar' => [],
          ],
        ],
      ],
      '2-1' => [
        'vfs://testFindFileUpward/foo',
        'a.txt',
        'foo/bar',
        [
          'foo' => [
            'bar' => [],
            'a.txt' => 'okay',
          ],
        ],
      ],
      '2-2' => [
        'vfs://testFindFileUpward/foo/bar',
        'a.txt',
        'foo/bar',
        [
          'foo' => [
            'bar' => [
              'a.txt' => 'okay',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesFindFileUpward
   *
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  public function testFindFileUpward(mixed $expected, string $fileName, string $relativeDirectory, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $absoluteDirectory = Path::join($vfs->url(), $relativeDirectory);

    static::assertSame($expected, Utils::findFileUpward($fileName, $absoluteDirectory));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesGetDirectDescendantDrupalPhpFiles(): array {
    return [
      'empty' => [
        [],
        '',
        [],
      ],
      'basic' => [
        [
          'a.engine',
          'a.install',
          'a.module',
          'a.profile',
          'a.theme',
          'a.php',
        ],
        'a',
        [
          'a' => [
            'a.engine' => '',
            'a.install' => '',
            'a.module' => '',
            'a.profile' => '',
            'a.theme' => '',
            'a.php' => '',
            'a.inc' => '',
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetDirectDescendantDrupalPhpFiles
   *
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  public function testGetDirectDescendantDrupalPhpFiles(mixed $expected, string $relativeDirectory, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $absoluteDirectory = Path::join($vfs->url(), $relativeDirectory);

    static::assertSame(
      $expected,
      Utils::getDirectDescendantDrupalPhpFiles($absoluteDirectory),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesEscapeYamlValueString(): array {
    return [
      'basic' => ["' foo bar '", ' foo bar '],
    ];
  }

  /**
   * @dataProvider casesEscapeYamlValueString
   */
  public function testEscapeYamlValueString(string $expected, string $text): void {
    static::assertSame($expected, Utils::escapeYamlValueString($text));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesChangeVersionNumberInYaml(): array {
    return [
      'first' => [
        implode(PHP_EOL, [
          'version: 8.x-1.1',
          'type: module',
          'core: 8.x',
          '',
        ]),
        implode(PHP_EOL, [
          'version: 8.x-1.0',
          'type: module',
          'core: 8.x',
          '',
        ]),
        '8.x-1.1',
      ],
      'middle' => [
        implode(PHP_EOL, [
          'type: module',
          'version: 8.x-1.1',
          'core: 8.x',
          '',
        ]),
        implode(PHP_EOL, [
          'type: module',
          'version: 8.x-1.0',
          'core: 8.x',
          '',
        ]),
        '8.x-1.1',
      ],
      'last-eol-yes' => [
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
          'version: 8.x-1.1',
          '',
        ]),
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
          'version: 8.x-1.0',
          '',
        ]),
        '8.x-1.1',
      ],
      'last-eol-no' => [
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
          'version: 8.x-1.1',
        ]),
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
          'version: 8.x-1.0',
        ]),
        '8.x-1.1',
      ],
      'only-eol-yes' => [
        implode(PHP_EOL, [
          'version: 8.x-1.1',
          '',
        ]),
        implode(PHP_EOL, [
          'version: 8.x-1.0',
          '',
        ]),
        '8.x-1.1',
      ],
      'only-eol-no' => [
        implode(PHP_EOL, [
          'version: 8.x-1.1',
        ]),
        implode(PHP_EOL, [
          'version: 8.x-1.0',
        ]),
        '8.x-1.1',
      ],
      'missing-eol-yes' => [
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
          'version: 8.x-1.1',
          '',
        ]),
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
          '',
        ]),
        '8.x-1.1',
      ],
      'missing-eol-no' => [
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
          'version: 8.x-1.1',
          '',
        ]),
        implode(PHP_EOL, [
          'type: module',
          'core: 8.x',
        ]),
        '8.x-1.1',
      ],
    ];
  }

  /**
   * @dataProvider casesChangeVersionNumberInYaml
   */
  public function testChangeVersionNumberInYaml(string $expected, string $yamlString, string $versionNumber): void {
    static::assertSame($expected, Utils::changeVersionNumberInYaml($yamlString, $versionNumber));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesEnsureTrailingEol(): array {
    return [
      'missing' => ['a' . PHP_EOL, 'a'],
      'already there' => ['a' . PHP_EOL, 'a' . PHP_EOL],
    ];
  }

  /**
   * @dataProvider casesEnsureTrailingEol
   */
  public function testEnsureTrailingEol(string $expected, string $text): void {
    Utils::ensureTrailingEol($text);
    static::assertSame($expected, $text);
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesPhpUnitSuiteNameToNamespace(): array {
    return [
      'unit' => ['Unit', 'unit'],
      'kernel' => ['Kernel', 'kernel'],
      'functional' => ['Functional', 'functional'],
      'functional-javascript' => [
        'FunctionalJavascript',
        'functional-javascript',
      ],
    ];
  }

  /**
   * @dataProvider casesPhpUnitSuiteNameToNamespace
   */
  public function testPhpUnitSuiteNameToNamespace(mixed $expected, string $suiteName): void {
    static::assertSame($expected, Utils::phpUnitSuiteNameToNamespace($suiteName));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesAggregateCommandErrors(): array {
    return [
      'empty' => [
        NULL,
        [],
      ],
      'basic' => [
        [
          'exitCode' => 3,
          'outputData' => implode(PHP_EOL, ['a', 'b', 'c']),
        ],
        [
          new CommandError('a', 1),
          new CommandError('b', 3),
          new CommandError('c', 2),
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesAggregateCommandErrors
   *
   * @phpstan-param null|array{exitCode: string, outputData: string} $expected
   * @phpstan-param \Consolidation\AnnotatedCommand\CommandError[] $commandErrors
   */
  public function testAggregateCommandErrors(?array $expected, array $commandErrors): void {
    $commandError = Utils::aggregateCommandErrors($commandErrors);
    if ($commandError === NULL) {
      static::assertSame($expected, $commandError);

      return;
    }

    static::assertSame($expected['exitCode'], $commandError->getExitCode());
    static::assertSame($expected['outputData'], $commandError->getOutputData());
  }

  public function testConvertStatusReportToRowsOfFields(): void {
    $statusReport = new StatusReport();
    $expected = [];
    $actualRowsOfFields = Utils::convertStatusReportToRowsOfFields($statusReport);
    static::assertSame($expected, $actualRowsOfFields->getArrayCopy());

    $statusReport->addEntries([
      (new StatusReportEntry())
        ->setId('a')
        ->setSeverity(RfcLogLevel::ERROR)
        ->setTitle('a-title')
        ->setDescription('a-description')
        ->setValue('a-value'),
      (new StatusReportEntry())
        ->setId('b')
        ->setSeverity(RfcLogLevel::WARNING)
        ->setTitle('b-title')
        ->setDescription('b-description')
        ->setValue('b-value'),
    ]);
    $expected = [
      'a' => [
        'id' => 'a',
        'title' => '<fg=red>a-title</>',
        'value' => 'a-value',
        'description' => 'a-description',
        'severity' => '<fg=red>3</>',
        'severityName' => '<fg=red>Error</>',
      ],
      'b' => [
        'id' => 'b',
        'title' => '<fg=yellow>b-title</>',
        'value' => 'b-value',
        'description' => 'b-description',
        'severity' => '<fg=yellow>4</>',
        'severityName' => '<fg=yellow>Warning</>',
      ],
    ];
    $actualRowsOfFields = Utils::convertStatusReportToRowsOfFields($statusReport);
    static::assertSame($expected, $actualRowsOfFields->getArrayCopy());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesFormatTextBySeverity(): array {
    return [
      'warning' => ['<fg=yellow>a</>', RfcLogLevel::WARNING, 'a'],
      'error' => ['<fg=red>b</>', RfcLogLevel::ERROR, 'b'],
      'unknown' => ['c', 42, 'c'],
    ];
  }

  /**
   * @dataProvider casesFormatTextBySeverity
   */
  public function testFormatTextBySeverity(string $expected, int $severity, string $text): void {
    static::assertSame($expected, Utils::formatTextBySeverity($severity, $text));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesParseDrupalExtensionVersionNumber(): array {
    return [
      'minimum' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => '',
          'extensionPreMajor' => 0,
          'extensionBuild' => '',
        ],
        '8.x-1.2',
      ],
      'with pre' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => 'beta',
          'extensionPreMajor' => 3,
          'extensionBuild' => '',
        ],
        '8.x-1.2-beta3',
      ],
      'with build' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => '',
          'extensionPreMajor' => 0,
          'extensionBuild' => 'foo.bar',
        ],
        '8.x-1.2+foo.bar',
      ],
      'full' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => 'beta',
          'extensionPreMajor' => 3,
          'extensionBuild' => 'foo.bar',
        ],
        '8.x-1.2-beta3+foo.bar',
      ],
    ];
  }

  /**
   * @dataProvider casesParseDrupalExtensionVersionNumber
   */
  public function testParseDrupalExtensionVersionNumber(mixed $expected, string $versionNumber): void {
    static::assertSame($expected, Utils::parseDrupalExtensionVersionNumber($versionNumber));
  }

  public function testParseDrupalExtensionVersionNumberFail(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionCode(1);

    Utils::parseDrupalExtensionVersionNumber('invalid');
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesDbUrl(): array {
    return [
      'sqlite - basic' => [
        'sqlite:///default__default.sqlite',
        [
          'driver' => 'sqlite',
          'database' => '/default__default.sqlite',
        ],
      ],
      'mysql - full' => [
        'mysql://a:b@c:d/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'password' => 'b',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-port;' => [
        'mysql://a:b@c/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'password' => 'b',
          'host' => 'c',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-password;' => [
        'mysql://a@c:d/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-prefix;' => [
        'mysql://a@c:d/e',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
        ],
      ],
      'mysql - no-all;' => [
        'mysql://c/e',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
        ],
      ],
      'mysql - prefix array default;' => [
        'mysql://c/e#f',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
          'prefix' => ['default' => 'f'],
        ],
      ],
      'mysql - prefix array no-default;' => [
        'mysql://c/e',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
          'prefix' => ['foo' => 'f'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesDbUrl
   *
   * @phpstan-param marvin-db-connection $connection
   */
  public function testDbUrl(mixed $expected, array $connection): void {
    static::assertSame($expected, Utils::dbUrl($connection));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesSplitPackageName(): array {
    return [
      'basic' => [
        [
          'vendor' => 'a',
          'name' => 'b',
        ],
        'a/b',
      ],
      'only name' => [
        [
          'vendor' => 'drupal',
          'name' => 'b',
        ],
        'b',
      ],
    ];
  }

  /**
   * @dataProvider casesSplitPackageName
   */
  public function testSplitPackageName(mixed $expected, string $packageName): void {
    static::assertSame($expected, Utils::splitPackageName($packageName));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesPhpVersionToPhpVersionId(): array {
    return [
      'empty' => ['', ''],
      '070102' => ['070102', '070102'],
      '0.0.0' => ['000000', '0.0.0'],
      '7.2.3' => ['070203', '7.2.3'],
    ];
  }

  /**
   * @dataProvider casesPhpVersionToPhpVersionId
   */
  public function testPhpVersionToPhpVersionId(string $expected, string $phpVersion): void {
    static::assertSame($expected, Utils::phpVersionToPhpVersionId($phpVersion));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesPhpErrorAll(): array {
    // E_ALL is same for PHP 7.1, 7.2 and 7.3.
    $eAll = 32767;

    return [
      'v7.1' => [$eAll, '7.1'],
      'v7.2' => [$eAll, '7.2'],
      'v7.3' => [$eAll, '7.3'],
      'v701' => [$eAll, '701'],
      'v702' => [$eAll, '702'],
      'v703' => [$eAll, '703'],
      '????' => [E_ALL, '???'],
    ];
  }

  /**
   * @dataProvider casesPhpErrorAll
   */
  public function testPhpErrorAll(int $expected, string $phpVersion): void {
    static::assertSame($expected, Utils::phpErrorAll($phpVersion));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesDetectDrupalRootDir(): array {
    return [
      'empty' => [
        'vendor/drupal',
        [],
      ],
      'type:drupal-core' => [
        'a/b',
        [
          'extra' => [
            'installer-paths' => [
              'a/b/core' => ['type:drupal-core'],
            ],
          ],
        ],
      ],
      'name:drupal/core' => [
        'a/b',
        [
          'extra' => [
            'installer-paths' => [
              'a/b/core' => ['drupal/core'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesDetectDrupalRootDir
   *
   * @phpstan-param array<string, mixed> $json
   */
  public function testDetectDrupalRootDir(string $expected, array $json): void {
    $vfsStructure = [
      'composer.json' => json_encode($json),
    ];

    $vfs = vfsStream::setup(
      __FUNCTION__ . '.' . $this->dataName(),
      NULL,
      $vfsStructure
    );
    $composerInfo = ComposerInfo::create($vfs->url(), 'composer.json');
    static::assertSame($expected, $composerInfo->getDrupalRootDir());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesSemverToDrupal(): array {
    return [
      'basic' => ['8.x-4.2', '8.x', '4.2.1'],
      'rc' => ['8.x-4.2-rc5', '8.x', '4.2.1-rc5'],
    ];
  }

  /**
   * @dataProvider casesSemverToDrupal
   */
  public function testSemverToDrupal(string $expected, string $core, string $semver): void {
    static::assertSame($expected, Utils::semverToDrupal($core, $semver));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesDrupalToSemver(): array {
    return [
      'basic' => ['1.0.0', '8.x-1.0'],
      'pre-release' => ['1.2.0-beta1', '8.x-1.2-beta1'],
      'pre-with-meta' => ['1.2.0-beta1+my-meta', '8.x-1.2-beta1+my-meta'],
      'meta' => ['1.2.0+my-meta', '8.x-1.2+my-meta'],
    ];
  }

  /**
   * @dataProvider casesDrupalToSemver
   */
  public function testDrupalToSemver(string $expected, string $drupalVersion): void {
    static::assertSame($expected, Utils::drupalToSemver($drupalVersion));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesIncrementSemVersion(): array {
    return [
      'major.basic' => ['2.0.0', '1.2.3', 'major'],
      'minor.basic' => ['1.3.0', '1.2.3', 'minor'],
      'patch.basic' => ['1.2.4', '1.2.3', 'patch'],
      'pre.basic-0' => ['1.2.4-alpha1', '1.2.3', 'pre-release'],
      'pre.basic-1' => ['1.2.3-alpha2', '1.2.3-alpha1', 'pre-release'],
      'pre.basic-2' => ['1.2.3-alpha.2', '1.2.3-alpha.1', 'pre-release'],
    ];
  }

  /**
   * @dataProvider casesIncrementSemVersion
   */
  public function testIncrementSemVersion(string $expected, string $semver, string $fragment): void {
    static::assertSame(
      $expected,
      (string) Utils::incrementSemVersion($semver, $fragment),
    );
  }

  public function testIncrementSemVersionFail(): void {
    static::expectException(\InvalidArgumentException::class);
    Utils::incrementSemVersion('1.2.3', 'not-exists');
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesPickFirstFile(): array {
    return [
      'empty' => [NULL, [], [], []],
      'not-exists' => [
        NULL,
        ['a'],
        ['b.txt'],
        [
          'a' => [
            'b.md' => '',
          ],
        ],
      ],
      'exists in current' => [
        [
          'dir' => '.',
          'file' => 'a.txt',
        ],
        ['.'],
        ['a.txt'],
        [
          'a.txt' => '',
        ],
      ],
      'exists' => [
        [
          'dir' => 'b',
          'file' => 'b.txt',
        ],
        ['c', 'b', 'a', '.'],
        ['b.txt', 'a.txt'],
        [
          'a' => [
            'a.txt' => '',
          ],
          'b' => [
            'a.txt' => '',
            'b.txt' => '',
          ],
          'c' => [
            'a.md' => '',
          ],
          'a.txt' => '',
          'b.txt' => '',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesPickFirstFile
   *
   * @phpstan-param null|array<string, mixed> $expected
   * @phpstan-param array<string> $dirs
   * @phpstan-param array<string> $files
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  public function testPickFirstFile(?array $expected, array $dirs, array $files, array $vfsStructure): void {
    $vfs = vfsStream::setup(
      __FUNCTION__ . '.' . $this->dataName(),
      NULL,
      $vfsStructure
    );

    $dirPrefix = $vfs->url();

    if ($expected) {
      $expected['dir'] = Path::join($dirPrefix, $expected['dir']);
    }

    foreach (array_keys($dirs) as $key) {
      $dirs[$key] = Path::join($dirPrefix, $dirs[$key]);
    }

    static::assertSame($expected, Utils::pickFirstFile($dirs, $files));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesGetTriStateCliOption(): array {
    return [
      'null' => ['', NULL, 'a'],
      'true' => ['--a', TRUE, 'a'],
      'false' => ['--no-a', FALSE, 'a'],
    ];
  }

  /**
   * @dataProvider casesGetTriStateCliOption
   */
  public function testGetTriStateCliOption(string $expected, ?bool $state, string $optionName): void {
    static::assertSame($expected, Utils::getTriStateCliOption($state, $optionName));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetExitCodeBasedOnSeverity(): array {
    return [
      'basic' => [0, RfcLogLevel::WARNING],
      'lowest error level' => [5, RfcLogLevel::WARNING, RfcLogLevel::WARNING],
    ];
  }

  /**
   * @dataProvider casesGetExitCodeBasedOnSeverity
   *
   * @phpstan-param int<0, 7> ...$args
   */
  public function testGetExitCodeBasedOnSeverity(mixed $expected, int ...$args): void {
    static::assertSame($expected, Utils::getExitCodeBasedOnSeverity(...$args));
  }

}
