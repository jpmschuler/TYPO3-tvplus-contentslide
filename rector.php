<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\CodeQuality\General\GeneralUtilityMakeInstanceToConstructorPropertyRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

$optionalPaths = [__DIR__ . '/ext_localconf.php'];
$relevantPaths = [
    __DIR__ . '/Classes',
];
foreach ($optionalPaths as $optionalPath) {
    if (file_exists($optionalPath)) {
        $relevantPaths[] = $optionalPath;
    }
}

return RectorConfig::configure()
    ->withPaths($relevantPaths)
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ])
    ->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.1.0-8.4.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '12.4.26-13.4.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
    ])
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        ConvertImplicitVariablesToExplicitGlobalsRector::class,
    ])
    ->withSkip([
        __DIR__ . '/.Build/',
        __DIR__ . '/**/Configuration/ExtensionBuilder/*',
    ])
    ->withSkip([GeneralUtilityMakeInstanceToConstructorPropertyRector::class]);
