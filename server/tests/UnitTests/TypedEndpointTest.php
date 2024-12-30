<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use PhpTypeScriptApi\TypedEndpoint;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-type FakeNestedThing array{id: int}
 * @phpstan-type FakeNamedThing array{id: int, name: string, nested: FakeNestedThing}
 *
 * @extends TypedEndpoint<
 *   array{
 *     mapping: array<string, number>,
 *     named: FakeNamedThing
 *   },
 *   string,
 * >
 */
class FakeTypedEndpoint extends TypedEndpoint {
    public mixed $handled_with_input = null;
    public mixed $handle_with_output = null;

    public static function getIdent(): string {
        return 'FakeTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        $this->logger?->info("Handling...");
        $this->handled_with_input = $input;
        return $this->handle_with_output;
    }
}

class FakeTransitiveTypedEndpoint extends FakeTypedEndpoint {
    public mixed $handled_with_input = null;
    public mixed $handle_with_output = null;

    public static function getIdent(): string {
        return 'FakeTransitiveTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        $this->logger?->info("Handling...");
        $this->handled_with_input = $input;
        return $this->handle_with_output;
    }
}

// TODO: Support @phpstan-import-type FakeNamedThing from FakeTypedEndpoint
/**
 * @phpstan-type FakeNestedThing array{id: int}
 * @phpstan-type FakeNamedThing array{id: int, name: string, nested: FakeNestedThing}
 *
 * @extends \PhpTypeScriptApi\TypedEndpoint<
 *   array{
 *     mapping: array<string, number>,
 *     named: FakeNamedThing
 *   },
 *   string,
 * >
 */
class FakeTypedEndpointWithErrors extends TypedEndpoint {
    public bool $handle_with_throttling = false;
    public bool $handle_with_error = false;
    public bool $handle_with_http_error = false;
    public bool $handle_with_validation_error = false;
    public mixed $handle_with_output = null;

    public static function getIdent(): string {
        return 'FakeTypedEndpointWithErrors';
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
 * @covers \PhpTypeScriptApi\TypedEndpoint
 */
final class TypedEndpointTest extends UnitTestCase {
    public const VALID_INPUT = [
        'mapping' => [],
        'named' => [
            'id' => 1,
            'name' => 'Fake Name',
            'nested' => ['id' => 11],
        ],
    ];

    public function testFakeTypedEndpoint(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->handle_with_output = 'test_output';
        $endpoint->setLogger($this->fakeLogger);
        $result = $endpoint->call($this::VALID_INPUT);
        $this->assertSame($this::VALID_INPUT, $endpoint->handled_with_input);
        $this->assertSame('test_output', $result);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointParseInputJson(): void {
        $content_json = '{"json":"input"}';
        $request = new Request([], [], [], [], [], [], $content_json);
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(['json' => 'input'], $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointParseInputGet(): void {
        $get_params = ['request' => json_encode(['got' => 'input'])];
        $request = new Request($get_params);
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(['got' => 'input'], $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointParseInputEmpty(): void {
        $request = new Request();
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(null, $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointGetNamedTsTypes(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            [
                'FakeNamedThing' => "{'id': number, 'name': string, 'nested': FakeNestedThing}",
                'FakeNestedThing' => "{'id': number}",
            ],
            $endpoint->getNamedTsTypes(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointGetRequestTsType(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            "{'mapping': {[key: string]: number}, 'named': FakeNamedThing}",
            $endpoint->getRequestTsType(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeEndpointGetResponseTsType(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame('string', $endpoint->getResponseTsType());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTransitiveTypedEndpoint(): void {
        try {
            new FakeTransitiveTypedEndpoint();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame(
                'Only classes directly extending TypedEndpoint may be used.',
                $exc->getMessage(),
            );
            $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithThrottling(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_throttling = true;
        try {
            $endpoint->call(null);
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
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        try {
            $endpoint->call(null);
            $this->fail('Error expected');
        } catch (HttpError $err) {
            $this->assertSame(400, $err->getCode());
            $this->assertSame('Bad input', $err->getMessage());
            $this->assertSame([
                'message' => 'Bad input',
                'error' => [
                    'type' => 'ValidationError',
                    'validationErrors' => ['.' => ['Value must be a list.']],
                ],
            ], $err->getStructuredAnswer());
            $this->assertSame([
                "WARNING Bad user request",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeEndpointWithExecutionError(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_error = true;
        try {
            $endpoint->call($this::VALID_INPUT);
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
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_http_error = true;
        try {
            $endpoint->call($this::VALID_INPUT);
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
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_validation_error = true;
        try {
            $endpoint->call($this::VALID_INPUT);
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
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = null;
        try {
            $endpoint->call($this::VALID_INPUT);
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
                    'validationErrors' => ['.' => ['Value must be of type string.']],
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
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = 'test';
        $result = $endpoint->call($this::VALID_INPUT);
        $this->assertSame('test', $result);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling with output...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }
}
