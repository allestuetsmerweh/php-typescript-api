<?php

/*
 * Do not use this file in your project!
 * Instead, require php-typescript-api in composer:
 *
 *     composer require allestuetsmerweh/php-typescript-api
 *
 */
include_once __DIR__.'/../../vendor/autoload.php';

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addPsr4('PhpTypeScriptApi\\', __DIR__.'/../../server/lib', true);
$classLoader->register();
