<?php

$example_api = require __DIR__.'/example_api.php';

file_put_contents(
    __DIR__.'/../web/ExampleApiTypes.ts',
    $example_api->getTypeScriptDefinition('ExampleApi')
);
