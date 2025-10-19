<?php

$loader = @include __DIR__ . '/../vendor/autoload.php';

if (! $loader) {
    $loader = @include __DIR__ . '/../../../autoload.php';
}

if (! $loader) {
    throw new RuntimeException('vendor/autoload.php could not be found. Did you run `php composer.phar install`?');
}
