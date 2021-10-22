<?php

$api = require_once __DIR__.'/example_api.php';

file_put_contents(
    __DIR__.'/../server/ExampleApi.ts',
    $api->getTypeScriptDefinition('ExampleApi')
);
