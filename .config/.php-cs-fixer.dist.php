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

// $overrides = [
//     'yoda_style' => ['identical' => false],
// ];

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                 => true,
        'array_syntax'           => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
            ],
        ],
    ])
    ->setCacheFile(__DIR__ . '/../build/.php-cs-fixer.cache')
    ->setFinder($finder)
;
