<?php

require_once __DIR__.'/../../lib/api/PhpTypeScriptApi.php';

$example_api = new PhpTypeScriptApi();
$example_api->registerEndpoint('divideNumbers', function () {
    require_once __DIR__.'/endpoints/DivideNumbersEndpoint.php';
    return new DivideNumbersEndpoint();
});
return $example_api;
