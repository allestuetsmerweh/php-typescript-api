<?php

include_once __DIR__.'/../vendor/autoload.php';

$classLoader = new Composer\Autoload\ClassLoader();
$classLoader->addPsr4('PhpTypeScriptApi\\', __DIR__.'/../../../server/lib', true);
$classLoader->addPsr4('PhpTypeScriptApi\\Tests\\', __DIR__.'/../../../server/tests', true);
$classLoader->addPsr4('PhpTypeScriptApi\\BackendTests\\', __DIR__, true);
$classLoader->register();
