<?php

declare(strict_types=1);

use PhpTypeScriptApi\Api;
use PhpTypeScriptApi\Fields\FieldTypes;

require_once __DIR__.'/../_common/UnitTestCase.php';

class FakeApiTestEndpoint1 extends Api\Endpoint {
    public $handled_with_input;
    public $handled_with_resource;
    public $handle_with_output;

    public function __construct($resource) {
        $this->resource = $resource;
    }

    public static function getIdent() {
        return 'FakeEndpoint1';
    }

    public function getResponseField() {
        return new FieldTypes\Field(['export_as' => 'SampleExport1']);
    }

    public function getRequestField() {
        return new FieldTypes\Field(['allow_null' => true]);
    }

    public function getSession() {
        return $this->session;
    }

    public function getServer() {
        return $this->server;
    }

    protected function handle($input) {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        return $this->handle_with_output;
    }
}

class FakeApiTestEndpoint2 extends Api\Endpoint {
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

    public function getSession() {
        return $this->session;
    }

    public function getServer() {
        return $this->server;
    }

    protected function handle($input) {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        return $this->handle_with_output;
    }
}

/**
 * @internal
 * @covers \PhpTypeScriptApi\Api\Api
 */
final class ApiTest extends UnitTestCase {
    public function testApi(): void {
        $fake_api = new Api\Api();
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
export enum FakeApiEndpoint {
    fakeEndpoint1 = 'fakeEndpoint1',
    fakeEndpoint2 = 'fakeEndpoint2',
}

type FakeApiEndpointMapping = {[key in FakeApiEndpoint]: {[fieldId: string]: any}};

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
}
