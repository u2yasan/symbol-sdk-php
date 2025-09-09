<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('vendor')
    ->exclude('build')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

if (is_dir(__DIR__ . '/src')) {
    $finder->in(__DIR__ . '/src');
} else {
    $finder->in(__DIR__)
           ->depth('== 0');
}

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)  // これが重要！
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder);
