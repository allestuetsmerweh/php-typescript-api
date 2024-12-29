<?php

use PhpTypeScriptApi\Api;

require_once __DIR__.'/autoload.php';

$example_api = new Api();

$example_api->registerEndpoint('divideNumbers', function () {
    require_once __DIR__.'/Endpoints/DivideNumbersEndpoint.php';
    return new DivideNumbersEndpoint();
});

$example_api->registerEndpoint('squareRoot', function () {
    require_once __DIR__.'/Endpoints/SquareRootEndpoint.php';
    return new SquareRootEndpoint();
});

$example_api->registerEndpoint('searchSwissPublicTransportConnection', function () {
    require_once __DIR__.'/Endpoints/SwissPublicTransportConnectionsEndpoint.php';
    return new SwissPublicTransportConnectionsEndpoint();
});

$example_api->registerEndpoint('empty', function () {
    require_once __DIR__.'/Endpoints/EmptyEndpoint.php';
    return new EmptyEndpoint();
});

// PHPStan-based typed endpoints

$example_api->registerEndpoint('divideNumbersTyped', function () {
    require_once __DIR__.'/Endpoints/DivideNumbersTypedEndpoint.php';
    return new DivideNumbersTypedEndpoint();
});

$example_api->registerEndpoint('squareRootTyped', function () {
    require_once __DIR__.'/Endpoints/SquareRootTypedEndpoint.php';
    return new SquareRootTypedEndpoint();
});

$example_api->registerEndpoint('searchSwissPublicTransportConnectionTyped', function () {
    require_once __DIR__.'/Endpoints/SwissPublicTransportConnectionsTypedEndpoint.php';
    return new SwissPublicTransportConnectionsTypedEndpoint();
});

$example_api->registerEndpoint('emptyTyped', function () {
    require_once __DIR__.'/Endpoints/EmptyTypedEndpoint.php';
    return new EmptyTypedEndpoint();
});

return $example_api;
