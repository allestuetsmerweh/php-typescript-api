<?php

declare(strict_types=1);

use PhpTypeScriptApi\Api;
use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;

require_once __DIR__.'/_common/UnitTestCase.php';

class FakeApiTestEndpoint1 extends Endpoint {
    public $handled_with_input;
    public $handled_with_resource;
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
        return $this->handle_with_output;
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
    public $responses = [];

    protected function respond($http_code, $response) {
        $this->responses[] = [
            'http_code' => $http_code,
            'response' => $response,
        ];
    }

    public function testOnlyGetSanitizedEndpointName($path_info) {
        return parent::getSanitizedEndpointName($path_info);
    }

    public function testOnlyServeEndpoint($endpoint_name) {
        return parent::serveEndpoint($endpoint_name);
    }
}

/**
 * @internal
 * @covers \PhpTypeScriptApi\Api
 */
final class ApiTest extends UnitTestCase {
    public function testApiGetTypeScriptDefinition(): void {
        $fake_api = new Api();
        $fake_api->registerEndpoint('fakeEndpoint1', function () {
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
        $this->assertSame($expected_output, $fake_api->getTypeScriptDefinition('FakeApi'));
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
            $this->assertSame('No path info', $exc->getMessage());
        }
        try {
            $fake_api->testOnlyGetSanitizedEndpointName('ínvãlïd_ĉĥàŕŝ');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame('No path info', $exc->getMessage());
        }
    }

    public function testApiServeEndpoint(): void {
        $fake_api = new FakeApiTestApi();
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint(
            'fakeEndpoint1',
            function () use ($fake_endpoint) {
                return $fake_endpoint;
            }
        );

        $fake_api->testOnlyServeEndpoint('fakeEndpoint1');

        $this->assertSame(
            [['http_code' => 200, 'response' => 'fake-output']],
            $fake_api->responses
        );
        $this->assertSame([], $fake_endpoint->handled_with_input);
        $this->assertSame('fake-resource', $fake_endpoint->handled_with_resource);
    }
}
