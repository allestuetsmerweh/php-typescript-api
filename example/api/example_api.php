<?php

use PhpTypeScriptApi\Api\Api;

require_once __DIR__.'/autoload.php';

$example_api = new Api();
$example_api->registerEndpoint('divideNumbers', function () {
    require_once __DIR__.'/endpoints/DivideNumbersEndpoint.php';
    return new DivideNumbersEndpoint();
});
return $example_api;
