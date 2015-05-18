<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../../vendor/autoload.php';

$loader->addPsr4('Example\\', __DIR__ . '/../src/');

return $loader;
