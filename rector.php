<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitSelfCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withImportNames(true, true, false)
    ->withSets([
        LevelSetList::UP_TO_PHP_84,

        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::DEAD_CODE,
        SetList::PRIVATIZATION,

        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,

        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ])
    ->withAttributesSets(all: true)
    ->withComposerBased(doctrine: true, symfony: true)
    ->withRules([
        PreferPHPUnitSelfCallRector::class,
    ])
    ->withSkip([
        PreferPHPUnitThisCallRector::class,
    ]);
