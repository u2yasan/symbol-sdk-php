<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\Privatization\Rector\Property\ChangeReadOnlyPropertyWithDefaultValueToConstantRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpSets(
        php83: true
    )
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withRules([
        // PHP 8.3 specific
        AddOverrideAttributeToOverriddenMethodsRector::class,
        
        // Type declarations
        DeclareStrictTypesRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromStrictScalarReturnExprRector::class,
        
        // Constructor promotion
        ClassPropertyAssignToConstructorPromotionRector::class,
        InlineConstructorDefaultToPropertyRector::class,
        
        // ReadOnly optimizations
        ReadOnlyClassRector::class,
        ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class,
        
        // Strict types
        NullToStrictStringFuncCallArgRector::class,
    ])
    ->withSkip([
        // Skipするルールがあれば追加
    ]);