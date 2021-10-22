<?php

include_once __DIR__.'/../../vendor/autoload.php';

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addPsr4('PhpTypeScriptApi\\', __DIR__.'/../../server/lib', true);
$classLoader->register();
