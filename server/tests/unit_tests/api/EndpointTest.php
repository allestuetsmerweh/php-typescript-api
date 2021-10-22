<?php

declare(strict_types=1);

use Monolog\Logger;

require_once __DIR__.'/../../../lib/api/Endpoint.php';
require_once __DIR__.'/../../../lib/fields/types/Field.php';
require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../common/UnitTestCase.php';

class FakeEndpoint extends Endpoint {
    public $handled_with_input;
    public $handled_with_resource;
    public $handle_with_output;

    public function __construct($resource) {
        $this->resource = $resource;
    }

    public static function getIdent() {
        return 'FakeEndpoint';
    }

    public function getResponseField() {
        return new Field([]);
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

class FakeEndpointWithErrors extends Endpoint {
    public $handle_with_throttling;
    public $handle_with_error;
    public $handle_with_http_error;
    public $handle_with_output;

    public static function getIdent() {
        return 'FakeEndpointWithErrors';
    }

    public function getResponseField() {
        return new Field(['allow_null' => false]);
    }

    public function getRequestField() {
        return new Field(['allow_null' => false]);
    }

    public function shouldFailThrottling() {
        return (bool) $this->handle_with_throttling;
    }

    protected function handle($input) {
        if ($this->handle_with_error) {
            throw new Exception("Fake Error", 1);
        }
        if ($this->handle_with_http_error) {
            throw new HttpError(418, "I'm a teapot");
        }
        return $this->handle_with_output;
    }
}

/**
 * @internal
 * @covers \Endpoint
 */
final class EndpointTest extends UnitTestCase {
    public function testFakeEndpoint(): void {
        $fake_server = ['name' => 'fake'];
        $logger = new Logger('EndpointTest');
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->handle_with_output = 'test_output';
        $endpoint->setServer($fake_server);
        $endpoint->setLogger($logger);
        $result = $endpoint->call(null);
        $this->assertSame(null, $endpoint->handled_with_input);
        $this->assertSame('test_output', $result);
        $this->assertSame('fake_resource', $endpoint->handled_with_resource);
        $this->assertSame($fake_server, $endpoint->getServer());
    }

    public function testFakeEndpointParseInput(): void {
        global $_GET, $_POST;
        $_GET = ['get_param' => json_encode('got')];
        $_POST = ['post_param' => json_encode('posted')];
        $endpoint = new FakeEndpoint('fake_resource');
        $parsed_input = $endpoint->parseInput();
        $this->assertSame(['post_param' => 'posted', 'get_param' => 'got'], $parsed_input);
    }

    public function testFakeEndpointSetupFunction(): void {
        global $_GET, $_POST;
        $endpoint = new FakeEndpoint('fake_resource');
        try {
            $endpoint->setup();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame('Setup function must be set', $exc->getMessage());
        }
        $endpoint->setSetupFunction(function ($endpoint) {
            $endpoint->setupCalled = true;
        });
        $endpoint->setup();
        $this->assertSame(true, $endpoint->setupCalled);
    }

    public function testFakeEndpointWithThrottling(): void {
        $logger = new Logger('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_throttling = true;
        try {
            $result = $endpoint->call(null);
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(429, $err->getCode());
        }
    }

    public function testFakeEndpointWithInvalidInput(): void {
        $logger = new Logger('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        try {
            $result = $endpoint->call(null);
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(400, $err->getCode());
        }
    }

    public function testFakeEndpointWithExecutionError(): void {
        $logger = new Logger('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_error = true;
        try {
            $result = $endpoint->call('test');
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(500, $err->getCode());
        }
    }

    public function testFakeEndpointWithExecutionHttpError(): void {
        $logger = new Logger('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_http_error = true;
        try {
            $result = $endpoint->call('test');
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(418, $err->getCode());
        }
    }

    public function testFakeEndpointWithInvalidOutput(): void {
        $logger = new Logger('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = null;
        try {
            $result = $endpoint->call('test');
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(500, $err->getCode());
        }
    }

    public function testFakeEndpointWithoutAnyErrors(): void {
        $logger = new Logger('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = 'test';
        $result = $endpoint->call('test');
        $this->assertSame('test', $result);
    }
}
