<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('tests-output')
    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setFinder($finder);
