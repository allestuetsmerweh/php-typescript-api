<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Api;
use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use PhpTypeScriptApi\TypedEndpoint;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class FakeApiTestEndpoint1 extends Endpoint {
    public mixed $handled_with_input = null;
    public mixed $handled_with_resource = null;
    public ?\Exception $handle_with_error = null;
    public mixed $handle_with_output = null;

    public mixed $resource = null;
    public bool $runtimeSetupCompleted = false;

    public function __construct(mixed $resource) {
        $this->resource = $resource;
    }

    public static function getIdent(): string {
        return 'FakeEndpoint1';
    }

    public function runtimeSetup(): void {
        $this->runtimeSetupCompleted = true;
    }

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\Field(['export_as' => 'SampleExport1']);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\Field(['allow_null' => true]);
    }

    protected function handle(mixed $input): mixed {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        if ($this->handle_with_error) {
            throw $this->handle_with_error;
        }
        return $this->handle_with_output;
    }

    public function testOnlyGetLogger(): ?LoggerInterface {
        return $this->logger;
    }
}

class FakeApiTestEndpoint2 extends Endpoint {
    public mixed $handled_with_input;
    public mixed $handled_with_resource;
    public mixed $handle_with_output;

    public mixed $resource;

    public function __construct(mixed $resource) {
        $this->resource = $resource;
    }

    public static function getIdent(): string {
        return 'FakeEndpoint2';
    }

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\Field(['allow_null' => false]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\Field(['export_as' => 'SampleExport2']);
    }

    protected function handle(mixed $input): mixed {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        return $this->handle_with_output;
    }
}

/**
 * @phpstan-type SampleTypedExport1 array{}
 *
 * @extends TypedEndpoint<?array{}, SampleTypedExport1>
 */
class FakeApiTestTypedEndpoint1 extends TypedEndpoint {
    public mixed $handled_with_input = null;
    public mixed $handled_with_resource = null;
    public ?\Exception $handle_with_error = null;
    public mixed $handle_with_output = null;

    public mixed $resource = null;
    public bool $runtimeSetupCompleted = false;

    public function __construct(mixed $resource) {
        parent::__construct();
        $this->resource = $resource;
    }

    public static function getIdent(): string {
        return 'FakeTypedEndpoint1';
    }

    public function runtimeSetup(): void {
        $this->runtimeSetupCompleted = true;
    }

    protected function handle(mixed $input): mixed {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        if ($this->handle_with_error) {
            throw $this->handle_with_error;
        }
        return $this->handle_with_output;
    }

    public function testOnlyGetLogger(): ?LoggerInterface {
        return $this->logger;
    }
}

/**
 * @phpstan-type SampleTypedExport2 array{}
 *
 * @extends TypedEndpoint<SampleTypedExport2, array{}>
 */
class FakeApiTestTypedEndpoint2 extends TypedEndpoint {
    public mixed $handled_with_input;
    public mixed $handled_with_resource;
    public mixed $handle_with_output;

    public mixed $resource;

    public function __construct(mixed $resource) {
        parent::__construct();
        $this->resource = $resource;
    }

    public static function getIdent(): string {
        return 'FakeTypedEndpoint2';
    }

    protected function handle(mixed $input): mixed {
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        return $this->handle_with_output;
    }
}

class FakeApiTestApi extends Api {
    public function testOnlyGetLogger(): ?LoggerInterface {
        return $this->logger;
    }

    public function testOnlyGetSanitizedEndpointName(string $path_info): string {
        return parent::getSanitizedEndpointName($path_info);
    }
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\Api
 */
final class ApiTest extends UnitTestCase {
    public function testApiGetTypeScriptDefinition(): void {
        $fake_api = $this->getFakeApi();

        $expected_output = <<<'ZZZZZZZZZZ'
            /** ### This file is auto-generated, modifying is futile! ### */

            export type SampleExport1 = unknown;

            export type SampleExport2 = unknown;

            export type SampleTypedExport1 = Record<string, never>;

            export type SampleTypedExport2 = Record<string, never>;

            // eslint-disable-next-line no-shadow
            export type FakeApiEndpoint =
                'fakeEndpoint1'|
                'fakeEndpoint2'|
                'fakeTypedEndpoint1'|
                'fakeTypedEndpoint2';

            type FakeApiEndpointMapping = {[key in FakeApiEndpoint]: unknown};

            export interface FakeApiRequests extends FakeApiEndpointMapping {
                fakeEndpoint1: unknown,
                fakeEndpoint2: SampleExport2,
                fakeTypedEndpoint1: (Record<string, never> | null),
                fakeTypedEndpoint2: SampleTypedExport2,
            }

            export interface FakeApiResponses extends FakeApiEndpointMapping {
                fakeEndpoint1: SampleExport1,
                fakeEndpoint2: unknown,
                fakeTypedEndpoint1: SampleTypedExport1,
                fakeTypedEndpoint2: Record<string, never>,
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

            export type SampleExport1 = unknown;

            export type SampleExport2 = unknown;

            // eslint-disable-next-line no-shadow
            export type FakeApiEndpoint =
                'fakeEndpoint1'|
                'fakeEndpoint1Again'|
                'fakeEndpoint2';

            type FakeApiEndpointMapping = {[key in FakeApiEndpoint]: unknown};

            export interface FakeApiRequests extends FakeApiEndpointMapping {
                fakeEndpoint1: unknown,
                fakeEndpoint1Again: unknown,
                fakeEndpoint2: SampleExport2,
            }

            export interface FakeApiResponses extends FakeApiEndpointMapping {
                fakeEndpoint1: SampleExport1,
                fakeEndpoint1Again: SampleExport1,
                fakeEndpoint2: unknown,
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
            ['fakeEndpoint1', 'fakeEndpoint2', 'fakeTypedEndpoint1', 'fakeTypedEndpoint2'],
            $fake_api->getEndpointNames()
        );
    }

    public function testApiGetEndpointByNameFromEndpoint(): void {
        $fake_api = $this->getFakeApi();
        $fake_endpoint_1 = $fake_api->getEndpointByName('fakeEndpoint1');
        if (!$fake_endpoint_1 instanceof FakeApiTestEndpoint1) {
            throw new \Exception("Must be a FakeApiTestEndpoint1");
        }
        $this->assertSame('fake-resource', $fake_endpoint_1->resource);
    }

    public function testApiGetEndpointByNameFromGetter(): void {
        $fake_api = $this->getFakeApi();
        $fake_endpoint_2 = $fake_api->getEndpointByName('fakeEndpoint2');
        if (!$fake_endpoint_2 instanceof FakeApiTestEndpoint2) {
            throw new \Exception("Must be a FakeApiTestEndpoint2");
        }
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

    public function testApiServeEndpoint(): void {
        global $_SERVER;
        $server_backup = $_SERVER;
        $_SERVER = [
            'PATH_INFO' => '/fakeEndpoint1',
        ];
        $fake_api = new FakeApiTestApi();
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint('fakeEndpoint1', $fake_endpoint);

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

    public function testApiServeGetter(): void {
        global $_SERVER;
        $server_backup = $_SERVER;
        $_SERVER = [
            'PATH_INFO' => '/fakeEndpoint2',
        ];
        $fake_api = new FakeApiTestApi();
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint(
            'fakeEndpoint2',
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
        $fake_api->setLogger($this->fakeLogger);
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
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testApiGetResponseFromEndpoint(): void {
        $fake_api = new FakeApiTestApi();
        $fake_api->setLogger($this->fakeLogger);
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint('fakeEndpoint1', $fake_endpoint);
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
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testApiGetResponseFromGetter(): void {
        $fake_api = new FakeApiTestApi();
        $fake_api->setLogger($this->fakeLogger);
        $fake_endpoint = new FakeApiTestEndpoint1('fake-resource');
        $fake_endpoint->handle_with_output = 'fake-output';
        $fake_api->registerEndpoint(
            'fakeEndpoint2',
            function () use ($fake_endpoint) {
                return $fake_endpoint;
            }
        );
        $request = new Request();
        $request->server->set('PATH_INFO', '/fakeEndpoint2');
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
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testApiGetResponseWithError(): void {
        $fake_api = new FakeApiTestApi();
        $fake_api->setLogger($this->fakeLogger);
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
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    protected function getFakeApi(): Api {
        $fake_api = new Api();
        // Directly register endpoint
        $fake_api->registerEndpoint(
            'fakeEndpoint1',
            new FakeApiTestEndpoint1('fake-resource'),
        );
        // Register endpoint getter
        $fake_api->registerEndpoint('fakeEndpoint2', function () {
            return new FakeApiTestEndpoint2('fake-resource');
        });
        // Directly register typed endpoint
        $fake_api->registerEndpoint(
            'fakeTypedEndpoint1',
            new FakeApiTestTypedEndpoint1('fake-resource'),
        );
        // Register typed endpoint getter
        $fake_api->registerEndpoint('fakeTypedEndpoint2', function () {
            return new FakeApiTestTypedEndpoint2('fake-resource');
        });
        return $fake_api;
    }
}
