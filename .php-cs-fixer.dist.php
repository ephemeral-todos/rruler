<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setRules([
        '@PER-CS' => true,
        '@Symfony' => true,
        'declare_strict_types' => true,

        // PHPDoc configuration
        'phpdoc_align' => [
            'align' => 'left',
            'tags' => [
                'method', 'param', 'property', 'property-read', 
                'property-write', 'return', 'throws', 'type', 'var'
            ]
        ],
        'phpdoc_separation' => [
            'groups' => [
                ['deprecated', 'link', 'see', 'since'],
                ['author', 'copyright', 'license'],
                ['category', 'package', 'subpackage'],
                ['property', 'property-read', 'property-write'],
                ['param', 'return']
            ]
        ],

        // Global namespace imports
        'global_namespace_import' => [
            'import_classes' => null,
            'import_constants' => true,
            'import_functions' => true
        ],

        // Specific rule exemptions for flexibility
        'no_empty_comment' => false,
        'single_line_throw' => false,
        'yoda_style' => false
    ])
    ->setFinder(
        (new Finder())
            ->name('*.php')
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/tests')
            ->ignoreDotFiles(true)
            ->ignoreVCSIgnored(true)
    );