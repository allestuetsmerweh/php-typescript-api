<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

class FakeEndpoint extends Endpoint {
    public mixed $handled_with_input = null;
    public mixed $handled_with_resource = null;
    public mixed $handle_with_output = null;
    public bool $ran_runtime_setup = false;

    public mixed $resource;

    public function __construct(mixed $resource) {
        $this->resource = $resource;
    }

    public function runtimeSetup(): void {
        $this->logger?->info("Runtime setup...");
        $this->ran_runtime_setup = true;
    }

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\Field(['export_as' => 'FakeResponse']);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\Field(['allow_null' => true]);
    }

    protected function handle(mixed $input): mixed {
        $this->logger?->info("Handling...");
        $this->handled_with_input = $input;
        $this->handled_with_resource = $this->resource;
        return $this->handle_with_output;
    }
}

class FakeEndpointWithErrors extends Endpoint {
    public bool $handle_with_throttling = false;
    public bool $handle_with_error = false;
    public bool $handle_with_http_error = false;
    public bool $handle_with_validation_error = false;
    public mixed $handle_with_output = null;

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\Field(['allow_null' => false]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\Field(['allow_null' => false]);
    }

    public function shouldFailThrottling(): bool {
        return (bool) $this->handle_with_throttling;
    }

    protected function handle(mixed $input): mixed {
        if ($this->handle_with_error) {
            $this->logger?->info("Handling with error...");
            throw new \Exception("Fake Error", 1);
        }
        if ($this->handle_with_http_error) {
            $this->logger?->info("Handling with HTTP error...");
            throw new HttpError(418, "I'm a teapot");
        }
        if ($this->handle_with_validation_error) {
            $this->logger?->info("Handling with validation error...");
            throw new ValidationError(['.' => ['Fundamental error']]);
        }
        $this->logger?->info("Handling with output...");
        return $this->handle_with_output;
    }
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\Endpoint
 */
final class EndpointTest extends UnitTestCase {
    public function testFakeEndpoint(): void {
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->handle_with_output = 'test_output';
        $endpoint->setLogger($this->fakeLogger);
        $result = $endpoint->call(null);
        $this->assertSame(null, $endpoint->handled_with_input);
        $this->assertSame('test_output', $result);
        $this->assertSame('fake_resource', $endpoint->handled_with_resource);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointParseInputJson(): void {
        $content_json = '{"json":"input"}';
        $request = new Request([], [], [], [], [], [], $content_json);
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(['json' => 'input'], $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointParseInputGet(): void {
        $get_params = ['request' => json_encode(['got' => 'input'])];
        $request = new Request($get_params);
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(['got' => 'input'], $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointParseInputEmpty(): void {
        $request = new Request();
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(null, $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointGetNamedTsTypes(): void {
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            ['FakeResponse' => "unknown"],
            $endpoint->getNamedTsTypes(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointGetRequestTsType(): void {
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame('unknown', $endpoint->getRequestTsType());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointGetResponseTsType(): void {
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame('FakeResponse', $endpoint->getResponseTsType());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointRuntimeSetup(): void {
        global $_GET, $_POST;
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->setup();
        $this->assertSame(true, $endpoint->ran_runtime_setup);
        $this->assertSame([
            "INFO Runtime setup...",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointSetupFunction(): void {
        global $_GET, $_POST;
        $logger = $this->fakeLogger;
        $endpoint = new FakeEndpoint('fake_resource');
        $endpoint->setLogger($this->fakeLogger);
        $setup_called = false;
        $endpoint->setSetupFunction(function ($endpoint) use ($logger, &$setup_called) {
            $logger->info("Setup...");
            $setup_called = true;
        });
        $endpoint->setup();
        $this->assertSame(true, $setup_called);
        $this->assertSame([
            "INFO Setup...",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointNoSetupImplemented(): void {
        global $_GET, $_POST;
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        try {
            $endpoint->setup();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame('Setup function must be set', $exc->getMessage());
            $this->assertSame([
                "CRITICAL Setup function must be set!",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithThrottling(): void {
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
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
            $this->assertSame([
                "ERROR Throttled user request",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithInvalidInput(): void {
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
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
            $this->assertSame([
                "WARNING Bad user request",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithExecutionError(): void {
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
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
            $this->assertSame([
                "INFO Valid user request",
                "INFO Handling with error...",
                "CRITICAL Unexpected endpoint error: Fake Error",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithExecutionHttpError(): void {
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
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
            $this->assertSame([
                "INFO Valid user request",
                "INFO Handling with HTTP error...",
                "WARNING HTTP error 418",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithExecutionValidationError(): void {
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
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
            $this->assertSame([
                "INFO Valid user request",
                "INFO Handling with validation error...",
                "WARNING Bad user request",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithInvalidOutput(): void {
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
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
            $this->assertSame([
                "INFO Valid user request",
                "INFO Handling with output...",
                "CRITICAL Bad output prohibited",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithoutAnyErrors(): void {
        $endpoint = new FakeEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = 'test';
        $result = $endpoint->call('test');
        $this->assertSame('test', $result);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling with output...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }
}
