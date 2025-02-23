<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

$config = (new Config())
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
    ]);

return $config;
