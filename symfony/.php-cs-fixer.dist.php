<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'tests',
        'var',
        'config',
    ])
    ->notPath([
        'src/Kernel.php',
        'public/index.php',
        'config/bundles.php',
        'config/reference.php',
        'importmap.php'
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
