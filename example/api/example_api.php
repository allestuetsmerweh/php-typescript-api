<?php

use PhpTypeScriptApi\Api;

require_once __DIR__.'/autoload.php';

$example_api = new Api();
$example_api->registerEndpoint('divideNumbers', function () {
    require_once __DIR__.'/endpoints/DivideNumbersEndpoint.php';
    return new DivideNumbersEndpoint();
});
$example_api->registerEndpoint('squareRoot', function () {
    require_once __DIR__.'/endpoints/SquareRootEndpoint.php';
    return new SquareRootEndpoint();
});
$example_api->registerEndpoint('searchSwissPublicTransportConnection', function () {
    require_once __DIR__.'/endpoints/SwissPublicTransportConnectionsEndpoint.php';
    return new SwissPublicTransportConnectionsEndpoint();
});
$example_api->registerEndpoint('empty', function () {
    require_once __DIR__.'/endpoints/EmptyEndpoint.php';
    return new EmptyEndpoint();
});
return $example_api;
