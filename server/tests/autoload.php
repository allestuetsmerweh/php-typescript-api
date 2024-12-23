<?php

include_once __DIR__.'/../../vendor/autoload.php';

$classLoader = new Composer\Autoload\ClassLoader();
$classLoader->addPsr4('PhpTypeScriptApi\\', __DIR__.'/../lib', true);
$classLoader->addPsr4('PhpTypeScriptApi\Tests\\', __DIR__, true);
$classLoader->addPsr4('PhpTypeScriptApi\BackendTests\\', __DIR__.'/../../example/tests/BackendTests', true);
$classLoader->register();
