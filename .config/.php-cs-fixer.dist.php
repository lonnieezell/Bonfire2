<?php

use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->in([
        __DIR__ . '/../src/',
        __DIR__ . '/../tests/',
    ])
    ->exclude([
        'build',
        'Views',
    ])
;

$overrides = [
    'yoda_style' => ['identical' => false],
];

$options = [
    'finder'    => $finder,
    'cacheFile' => '../build/.php-cs-fixer.cache',
];

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setCacheFile(__DIR__.'/../build/.php-cs-fixer.cache')
    ->setFinder($finder)
;
