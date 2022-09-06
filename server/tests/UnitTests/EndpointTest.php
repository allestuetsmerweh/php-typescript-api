<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\Tests\Fake\FakeLogger;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

class FakeEndpoint extends Endpoint {
    public $handled_with_input;
    public $handled_with_resource;
    public $handle_with_output;
    public $ran_runtime_setup;

    public function __construct($resource) {
        $this->resource = $resource;
    }

    public static function getIdent() {
        return 'FakeEndpoint';
    }

    public function runtimeSetup() {
        $this->ran_runtime_setup = true;
    }

    public function getResponseField() {
        return new FieldTypes\Field([]);
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

class FakeEndpointWithErrors extends Endpoint {
    public $handle_with_throttling;
    public $handle_with_error;
    public $handle_with_http_error;
    public $handle_with_validation_error;
    public $handle_with_output;

    public static function getIdent() {
        return 'FakeEndpointWithErrors';
    }

    public function getResponseField() {
        return new FieldTypes\Field(['allow_null' => false]);
    }

    public function getRequestField() {
        return new FieldTypes\Field(['allow_null' => false]);
    }

    public function shouldFailThrottling() {
        return (bool) $this->handle_with_throttling;
    }

    protected function handle($input) {
        if ($this->handle_with_error) {
            throw new \Exception("Fake Error", 1);
        }
        if ($this->handle_with_http_error) {
            throw new HttpError(418, "I'm a teapot");
        }
        if ($this->handle_with_validation_error) {
            throw new ValidationError(['.' => ['Fundamental error']]);
        }
        return $this->handle_with_output;
    }
}

/**
 * @internal
 * @covers \PhpTypeScriptApi\Endpoint
 */
final class EndpointTest extends UnitTestCase {
    public function testFakeEndpoint(): void {
        $fake_server = ['name' => 'fake'];
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->handle_with_output = 'test_output';
        $endpoint->setLogger($logger);
        $result = $endpoint->call(null);
        $this->assertSame(null, $endpoint->handled_with_input);
        $this->assertSame('test_output', $result);
        $this->assertSame('fake_resource', $endpoint->handled_with_resource);
    }

    public function testFakeEndpointParseInput(): void {
        global $_GET, $_POST;
        $_GET = ['get_param' => json_encode('got')];
        $_POST = ['post_param' => json_encode('posted')];
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($logger);
        $parsed_input = $endpoint->parseInput();
        $this->assertSame(['post_param' => 'posted', 'get_param' => 'got'], $parsed_input);
    }

    public function testFakeEndpointRuntimeSetup(): void {
        global $_GET, $_POST;
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setup();
        $this->assertSame(true, $endpoint->ran_runtime_setup);
    }

    public function testFakeEndpointSetupFunction(): void {
        global $_GET, $_POST;
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setSetupFunction(function ($endpoint) {
            $endpoint->setupCalled = true;
        });
        $endpoint->setup();
        $this->assertSame(true, $endpoint->setupCalled);
    }

    public function testFakeEndpointNoSetupImplemented(): void {
        global $_GET, $_POST;
        $endpoint = new FakeEndpointWithErrors('fake_resource');
        try {
            $endpoint->setup();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame('Setup function must be set', $exc->getMessage());
        }
    }

    public function testFakeEndpointWithThrottling(): void {
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_throttling = true;
        try {
            $result = $endpoint->call(null);
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(429, $err->getCode());
            $this->assertSame('Too many requests', $err->getMessage());
            $this->assertSame([
                'message' => 'Too many requests',
                'error' => true,
            ], $err->getStructuredAnswer());
        }
    }

    public function testFakeEndpointWithInvalidInput(): void {
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        try {
            $result = $endpoint->call(null);
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(400, $err->getCode());
            $this->assertSame('Bad input', $err->getMessage());
            $this->assertSame([
                'message' => 'Bad input',
                'error' => [
                    'type' => 'ValidationError',
                    'validationErrors' => ['.' => ['Field can not be empty.']],
                ],
            ], $err->getStructuredAnswer());
        }
    }

    public function testFakeEndpointWithExecutionError(): void {
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_error = true;
        try {
            $result = $endpoint->call('test');
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(500, $err->getCode());
            $this->assertSame(
                'An error occurred. Please try again later.',
                $err->getMessage()
            );
            $this->assertSame([
                'message' => 'An error occurred. Please try again later.',
                'error' => true,
            ], $err->getStructuredAnswer());
        }
    }

    public function testFakeEndpointWithExecutionHttpError(): void {
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_http_error = true;
        try {
            $result = $endpoint->call('test');
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(418, $err->getCode());
            $this->assertSame('I\'m a teapot', $err->getMessage());
            $this->assertSame([
                'message' => 'I\'m a teapot',
                'error' => true,
            ], $err->getStructuredAnswer());
        }
    }

    public function testFakeEndpointWithExecutionValidationError(): void {
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_validation_error = true;
        try {
            $result = $endpoint->call('test');
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(400, $err->getCode());
            $this->assertSame('Bad input', $err->getMessage());
            $this->assertSame([
                'message' => 'Bad input',
                'error' => [
                    'type' => 'ValidationError',
                    'validationErrors' => ['.' => ['Fundamental error']],
                ],
            ], $err->getStructuredAnswer());
        }
    }

    public function testFakeEndpointWithInvalidOutput(): void {
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = null;
        try {
            $result = $endpoint->call('test');
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(500, $err->getCode());
            $this->assertSame(
                'An error occurred. Please try again later.',
                $err->getMessage()
            );
            $this->assertSame([
                'message' => 'An error occurred. Please try again later.',
                'error' => [
                    'type' => 'ValidationError',
                    'validationErrors' => ['.' => ['Field can not be empty.']],
                ],
            ], $err->getStructuredAnswer());
        }
    }

    public function testFakeEndpointWithoutAnyErrors(): void {
        $logger = FakeLogger::create('EndpointTest');
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($logger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = 'test';
        $result = $endpoint->call('test');
        $this->assertSame('test', $result);
    }
}
