<?php

declare(strict_types=1);

require_once __DIR__.'/../../../lib/api/PhpTypeScriptApi.php';
require_once __DIR__.'/../common/UnitTestCase.php';

class FakePhpTypeScriptApiTestEndpoint1 extends Endpoint {
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
        return new Field(['export_as' => 'SampleExport1']);
    }

    public function getRequestField() {
        return new Field(['allow_null' => true]);
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

class FakePhpTypeScriptApiTestEndpoint2 extends Endpoint {
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
        return new Field(['allow_null' => false]);
    }

    public function getRequestField() {
        return new Field(['export_as' => 'SampleExport2']);
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
 * @covers \PhpTypeScriptApi
 */
final class PhpTypeScriptApiTest extends UnitTestCase {
    public function testPhpTypeScriptApi(): void {
        $fake_api = new PhpTypeScriptApi();
        $fake_api->registerEndpoint('fakeEndpoint1', function () {
            return new FakePhpTypeScriptApiTestEndpoint1('fake-resource');
        });
        $fake_api->registerEndpoint('fakeEndpoint2', function () {
            return new FakePhpTypeScriptApiTestEndpoint2('fake-resource');
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
