<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Api;
use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\Tests\Fake\FakeLogger;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

class FakeApiTestEndpoint1 extends Endpoint {
    public $handled_with_input;
    public $handled_with_resource;
    public $handle_with_error;
    public $handle_with_output;

    public function __construct($resource) {
        $this->resource = $resource;
    }

    public static function getIdent() {
        return 'FakeEndpoint1';
    }

    public function runtimeSetup() {
        $this->runtimeSetupCompleted = true;
    }

    public function getResponseField() {
        return new FieldTypes\Field(['export_as' => 'SampleExport1']);
    }

    public function getRequestField() {
        return new FieldTypes\Field(['allow_null' => true]);
    }

    protected function handle($input) {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        if ($this->handle_with_error) {
            throw $this->handle_with_error;
        }
        return $this->handle_with_output;
    }

    public function testOnlyGetLogger() {
        return $this->logger;
    }
}

class FakeApiTestEndpoint2 extends Endpoint {
    public $handled_with_input;
    public $handled_with_resource;
    public $handle_with_output;

    public function __construct($resource) {
        $this->resource = $resource;
    }

    public static function getIdent() {
        return 'FakeEndpoint2';
    }

    public function getResponseField() {
        return new FieldTypes\Field(['allow_null' => false]);
    }

    public function getRequestField() {
        return new FieldTypes\Field(['export_as' => 'SampleExport2']);
    }

    protected function handle($input) {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        return $this->handle_with_output;
    }
}

class FakeApiTestApi extends Api {
    public function testOnlyGetLogger() {
        return $this->logger;
    }

    public function testOnlyGetSanitizedEndpointName($path_info) {
        return parent::getSanitizedEndpointName($path_info);
    }
}

/**
 * @internal
 * @covers \PhpTypeScriptApi\Api
 */
final class ApiTest extends UnitTestCase {
    public function testApiGetTypeScriptDefinition(): void {
        $fake_api = $this->getFakeApi();

        $expected_output = <<<'ZZZZZZZZZZ'
/** ### This file is auto-generated, modifying is futile! ### */

export type SampleExport1 = any;

export type SampleExport2 = any;

// eslint-disable-next-line no-shadow
export type FakeApiEndpoint =
    'fakeEndpoint1'|
    'fakeEndpoint2';

type FakeApiEndpointMapping = {[key in FakeApiEndpoint]: any};

export interface FakeApiRequests extends FakeApiEndpointMapping {
    fakeEndpoint1: any,
    fakeEndpoint2: SampleExport2,
}

export interface FakeApiResponses extends FakeApiEndpointMapping {
    fakeEndpoint1: SampleExport1,
    fakeEndpoint2: any,
}


ZZZZZZZZZZ;
        $this->assertSame(
            $expected_output,
            $fake_api->getTypeScriptDefinition('FakeApi')
        );
    }

    public function testApiGetTypeScriptDefinitionDuplicateExport(): void {
        $fake_api = new Api();
        $fake_api->registerEndpoint('fakeEndpoint1', function () {
            return new FakeApiTestEndpoint1('fake-resource');
        });
        $fake_api->registerEndpoint('fakeEndpoint1Again', function () {
            return new FakeApiTestEndpoint1('fake-resource');
        });
        $fake_api->registerEndpoint('fakeEndpoint2', function () {
            return new FakeApiTestEndpoint2('fake-resource');
        });

        $expected_output = <<<'ZZZZZZZZZZ'
/** ### This file is auto-generated, modifying is futile! ### */

export type SampleExport1 = any;

export type SampleExport2 = any;

// eslint-disable-next-line no-shadow
export type FakeApiEndpoint =
    'fakeEndpoint1'|
    'fakeEndpoint1Again'|
    'fakeEndpoint2';

type FakeApiEndpointMapping = {[key in FakeApiEndpoint]: any};

export interface FakeApiRequests extends FakeApiEndpointMapping {
    fakeEndpoint1: any,
    fakeEndpoint1Again: any,
    fakeEndpoint2: SampleExport2,
}

export interface FakeApiResponses extends FakeApiEndpointMapping {
    fakeEndpoint1: SampleExport1,
    fakeEndpoint1Again: SampleExport1,
    fakeEndpoint2: any,
}


ZZZZZZZZZZ;
        $this->assertSame(
            $expected_output,
            $fake_api->getTypeScriptDefinition('FakeApi')
        );
    }

    public function testApiGetEndpointNames(): void {
        $fake_api = $this->getFakeApi();
        $this->assertSame(
            ['fakeEndpoint1', 'fakeEndpoint2'],
            $fake_api->getEndpointNames()
        );
    }

    public function testApiGetEndpointByName(): void {
        $fake_api = $this->getFakeApi();
        $fake_endpoint_2 = $fake_api->getEndpointByName('fakeEndpoint2');
        $this->assertSame(true, $fake_endpoint_2 instanceof FakeApiTestEndpoint2);
        $this->assertSame('fake-resource', $fake_endpoint_2->resource);
    }

    public function testApiGetInexistentEndpointByName(): void {
        $fake_api = $this->getFakeApi();
        $inexistent_endpoint = $fake_api->getEndpointByName('inexistent');
        $this->assertSame(null, $inexistent_endpoint);
    }

    public function testApiGetSanitizedEndpointName(): void {
        $fake_api = new FakeApiTestApi();
        $this->assertSame(
            'test1',
            $fake_api->testOnlyGetSanitizedEndpointName('/test1')
        );
        try {
            $fake_api->testOnlyGetSanitizedEndpointName('missing_slash');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame('Invalid endpoint', $exc->getMessage());
        }
        try {
            $fake_api->testOnlyGetSanitizedEndpointName('ínvãlïd_ĉĥàŕŝ');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame('Invalid endpoint', $exc->getMessage());
        }
    }

    public function testApiServe(): void {
        global $_SERVER;
        $server_backup = $_SERVER;
        $_SERVER = [
            'PATH_INFO' => '/fakeEndpoint1',
        ];
        $fake_api = new FakeApiTestApi();
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint(
            'fakeEndpoint1',
            function () use ($fake_endpoint) {
                return $fake_endpoint;
            }
        );

        ob_start();
        $fake_api->serve();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame(json_encode('fake-output'), $output);

        $this->assertSame(null, $fake_endpoint->handled_with_input);
        $this->assertSame('fake-resource', $fake_endpoint->handled_with_resource);
        $this->assertSame(null, $fake_api->testOnlyGetLogger());
        $this->assertSame(true, $fake_endpoint->testOnlyGetLogger() instanceof \Monolog\Logger);

        $_SERVER = $server_backup;
    }

    public function testApiGetResponseWithoutLogger(): void {
        $fake_api = new FakeApiTestApi();
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint(
            'fakeEndpoint1',
            function () use ($fake_endpoint) {
                return $fake_endpoint;
            }
        );
        $request = new Request();
        $request->server->set('PATH_INFO', '/fakeEndpoint1');
        $request->server->set('HTTP_ACCEPT_LANGUAGE', 'en');

        $response = $fake_api->getResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(null, $response->getCharset());
        $this->assertSame(json_encode('fake-output'), $response->getContent());

        $this->assertSame(null, $fake_endpoint->handled_with_input);
        $this->assertSame('fake-resource', $fake_endpoint->handled_with_resource);
        $this->assertSame(null, $fake_api->testOnlyGetLogger());
        $this->assertSame(true, $fake_endpoint->testOnlyGetLogger() instanceof \Monolog\Logger);
    }

    public function testApiServeInexistentEndpoint(): void {
        $fake_api = new FakeApiTestApi();
        $logger = FakeLogger::create('ApiTest');
        $fake_api->setLogger($logger);
        $request = new Request();
        $request->server->set('PATH_INFO', '/inexistent');
        $request->server->set('HTTP_ACCEPT_LANGUAGE', 'en');

        $response = $fake_api->getResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(null, $response->getCharset());
        $this->assertSame(json_encode([
            'message' => 'Invalid endpoint',
            'error' => true,
        ]), $response->getContent());

        $this->assertSame([
            'WARNING Invalid endpoint called: inexistent',
        ], $logger->handler->getPrettyRecords());
    }

    public function testApiGetResponse(): void {
        $fake_api = new FakeApiTestApi();
        $logger = FakeLogger::create('ApiTest');
        $fake_api->setLogger($logger);
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint(
            'fakeEndpoint1',
            function () use ($fake_endpoint) {
                return $fake_endpoint;
            }
        );
        $request = new Request();
        $request->server->set('PATH_INFO', '/fakeEndpoint1');
        $request->server->set('HTTP_ACCEPT_LANGUAGE', 'en');

        $response = $fake_api->getResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(null, $response->getCharset());
        $this->assertSame(json_encode('fake-output'), $response->getContent());

        $this->assertSame(null, $fake_endpoint->handled_with_input);
        $this->assertSame('fake-resource', $fake_endpoint->handled_with_resource);

        $this->assertSame([
            'INFO Valid user request',
            'INFO Valid user response',
        ], $logger->handler->getPrettyRecords());
    }

    public function testApiGetResponseWithError(): void {
        $fake_api = new FakeApiTestApi();
        $logger = FakeLogger::create('ApiTest');
        $fake_api->setLogger($logger);
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_error = new \Exception('test_error');
        $fake_api->registerEndpoint(
            'fakeEndpoint1',
            function () use ($fake_endpoint) {
                return $fake_endpoint;
            }
        );
        $request = new Request();
        $request->server->set('PATH_INFO', '/fakeEndpoint1');
        $request->server->set('HTTP_ACCEPT_LANGUAGE', 'en');

        $response = $fake_api->getResponse($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(null, $response->getCharset());
        $this->assertSame(json_encode([
            'message' => 'An error occurred. Please try again later.',
            'error' => true,
        ]), $response->getContent());

        $this->assertSame(null, $fake_endpoint->handled_with_input);
        $this->assertSame('fake-resource', $fake_endpoint->handled_with_resource);

        $this->assertSame([
            'INFO Valid user request',
            'CRITICAL Unexpected endpoint error: test_error',
        ], $logger->handler->getPrettyRecords());
    }

    protected function getFakeApi() {
        $fake_api = new Api();
        $fake_api->registerEndpoint('fakeEndpoint1', function () {
            return new FakeApiTestEndpoint1('fake-resource');
        });
        $fake_api->registerEndpoint('fakeEndpoint2', function () {
            return new FakeApiTestEndpoint2('fake-resource');
        });
        return $fake_api;
    }
}
