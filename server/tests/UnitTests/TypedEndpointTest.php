<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\PhpStan\IsoDate;
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
 *     named: FakeNamedThing,
 *     date: IsoDate,
 *   },
 *   array{
 *     mapping: array<string, number>,
 *     named: FakeNamedThing,
 *     date: IsoDate,
 *   }
 * >
 */
class FakeTypedEndpoint extends TypedEndpoint {
    public mixed $handled_with_input = null;
    public mixed $handle_with_output = null;
    public bool $ran_runtime_setup = false;

    public static function getApiObjectClasses(): array {
        return [IsoDate::class];
    }

    public static function getIdent(): string {
        return 'FakeTypedEndpoint';
    }

    public function runtimeSetup(): void {
        $this->logger?->info("Runtime setup...");
        $this->ran_runtime_setup = true;
    }

    protected function handle(mixed $input): mixed {
        $this->logger?->info("Handling...");
        $this->handled_with_input = $input;
        return $this->handle_with_output;
    }
}

/**
 * @template In
 * @template Out
 *
 * @phpstan-type Input array{in: In}
 *
 * @extends TypedEndpoint<Input, Out>
 */
abstract class FakeIntermediateGenericTypedEndpoint extends TypedEndpoint {
}

/**
 * @extends FakeIntermediateGenericTypedEndpoint<int, string>
 */
class FakeLeafGenericTypedEndpoint extends FakeIntermediateGenericTypedEndpoint {
    public mixed $handled_with_input = null;
    public mixed $handle_with_output = null;

    public static function getApiObjectClasses(): array {
        return [];
    }

    public static function getIdent(): string {
        return 'FakeLeafGenericTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        $this->logger?->info("Handling...");
        $this->handled_with_input = $input;
        return $this->handle_with_output;
    }
}

class FakeTransitiveTypedEndpoint extends FakeTypedEndpoint {
}

// TODO: Support @phpstan-import-type FakeNamedThing from FakeTypedEndpoint
/**
 * @phpstan-type FakeNestedThing array{id: int}
 * @phpstan-type FakeNamedThing array{id: int, name: string, nested: FakeNestedThing}
 *
 * @extends \PhpTypeScriptApi\TypedEndpoint<
 *   array{
 *     mapping: array<string, number>,
 *     named: FakeNamedThing,
 *     date: \PhpTypeScriptApi\PhpStan\IsoDate,
 *   },
 *   array{
 *     mapping: array<string, number>,
 *     named: FakeNamedThing,
 *     date: \PhpTypeScriptApi\PhpStan\IsoDate,
 *   },
 * >
 */
class FakeTypedEndpointWithErrors extends TypedEndpoint {
    public bool $handle_with_throttling = false;
    public bool $handle_with_error = false;
    public bool $handle_with_http_error = false;
    public bool $handle_with_validation_error = false;
    public mixed $handle_with_output = null;

    public static function getApiObjectClasses(): array {
        return [IsoDate::class];
    }

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
class TypedEndpointTest extends UnitTestCase {
    /**
     * @var array{
     *     mapping: array<string, number>,
     *     named: array{id: int, name: string, nested: array{id: int}},
     *     date: string,
     *   }
     */
    protected static array $raw_input;
    /**
     * @var array{
     *     mapping: array<string, number>,
     *     named: array{id: int, name: string, nested: array{id: int}},
     *     date: \PhpTypeScriptApi\PhpStan\IsoDate,
     *   }
     */
    protected static array $input;

    public static function setUpBeforeClass(): void {
        self::$raw_input = [
            'mapping' => [],
            'named' => [
                'id' => 1,
                'name' => 'Fake Name',
                'nested' => ['id' => 11],
            ],
            'date' => '2024-12-31',
        ];
        self::$input = [
            ...self::$raw_input,
            'date' => new IsoDate('2024-12-31'),
        ];
    }

    public function testFakeTypedEndpoint(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->handle_with_output = self::$raw_input;
        $endpoint->setLogger($this->fakeLogger);
        $result = $endpoint->call(self::$raw_input);
        $this->assertEquals(self::$input, $endpoint->handled_with_input);
        $this->assertEquals(self::$input, $result);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointWithApiObject(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->handle_with_output = self::$input;
        $endpoint->setLogger($this->fakeLogger);
        $result = $endpoint->call(self::$input);
        $this->assertEquals(self::$input, $endpoint->handled_with_input);
        $this->assertEquals(self::$input, $result);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointParseInputJson(): void {
        $content_json = '{"json":"input"}';
        $request = new Request([], [], [], [], [], [], $content_json);
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(['json' => 'input'], $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointParseInputGet(): void {
        $get_params = ['request' => json_encode(['got' => 'input'])];
        $request = new Request($get_params);
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(['got' => 'input'], $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointParseInputEmpty(): void {
        $request = new Request();
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $parsed_input = $endpoint->parseInput($request);
        $this->assertSame(null, $parsed_input);
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointGetNamedTsTypes(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            [
                'FakeNamedThing' => "{'id': number, 'name': string, 'nested': FakeNestedThing}",
                'IsoDate' => "string",
                'FakeNestedThing' => "{'id': number}",
            ],
            $endpoint->getNamedTsTypes(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointGetRequestTsType(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            "{'mapping': {[key: string]: number}, 'named': FakeNamedThing, 'date': IsoDate}",
            $endpoint->getRequestTsType(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointGetResponseTsType(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            "{'mapping': {[key: string]: number}, 'named': FakeNamedThing, 'date': IsoDate}",
            $endpoint->getResponseTsType(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTransitiveTypedEndpoint(): void {
        $endpoint = new FakeTransitiveTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            "{'mapping': {[key: string]: number}, 'named': FakeNamedThing, 'date': IsoDate}",
            $endpoint->getRequestTsType()
        );
        $this->assertSame(
            "{'mapping': {[key: string]: number}, 'named': FakeNamedThing, 'date': IsoDate}",
            $endpoint->getResponseTsType()
        );
        $this->assertSame([
            'FakeNamedThing' => "{'id': number, 'name': string, 'nested': FakeNestedThing}",
            'IsoDate' => "string",
            'FakeNestedThing' => "{'id': number}",
        ], $endpoint->getNamedTsTypes());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeLeafGenericTypedEndpoint(): void {
        $endpoint = new FakeLeafGenericTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame('Input', $endpoint->getRequestTsType());
        try {
            $endpoint->getResponseTsType();
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('Unknown IdentifierTypeNode name: Out', $th->getMessage());
        }
        try {
            $endpoint->getNamedTsTypes();
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('Unknown IdentifierTypeNode name: Out', $th->getMessage());
        }
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointRuntimeSetup(): void {
        global $_GET, $_POST;
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->setup();
        $this->assertSame(true, $endpoint->ran_runtime_setup);
        $this->assertSame([
            "INFO Runtime setup...",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointNoSetupImplemented(): void {
        global $_GET, $_POST;
        $endpoint = new FakeTypedEndpointWithErrors();
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

    public function testFakeTypedEndpointWithThrottling(): void {
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

    public function testFakeTypedEndpointWithErrorsGetNamedTsTypes(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            [
                'FakeNamedThing' => "{'id': number, 'name': string, 'nested': FakeNestedThing}",
                '_PhpTypeScriptApi_PhpStan_IsoDate' => "string",
                'FakeNestedThing' => "{'id': number}",
            ],
            $endpoint->getNamedTsTypes(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointWithErrorsGetRequestTsType(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            "{'mapping': {[key: string]: number}, 'named': FakeNamedThing, 'date': _PhpTypeScriptApi_PhpStan_IsoDate}",
            $endpoint->getRequestTsType(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointWithErrorsGetResponseTsType(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame(
            "{'mapping': {[key: string]: number}, 'named': FakeNamedThing, 'date': _PhpTypeScriptApi_PhpStan_IsoDate}",
            $endpoint->getResponseTsType(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointWithInvalidInput(): void {
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
                    'validationErrors' => ['.' => ['Value must be an object.']],
                ],
            ], $err->getStructuredAnswer());
            $this->assertSame([
                "WARNING Bad user request",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeTypedEndpointWithExecutionError(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_error = true;
        try {
            $endpoint->call(self::$raw_input);
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

    public function testFakeTypedEndpointWithExecutionHttpError(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_http_error = true;
        try {
            $endpoint->call(self::$raw_input);
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

    public function testFakeTypedEndpointWithExecutionValidationError(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_validation_error = true;
        try {
            $endpoint->call(self::$raw_input);
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

    public function testFakeTypedEndpointWithInvalidOutput(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = null;
        try {
            $endpoint->call(self::$raw_input);
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
                    'validationErrors' => ['.' => ['Value must be an object.']],
                ],
            ], $err->getStructuredAnswer());
            $this->assertSame([
                "INFO Valid user request",
                "INFO Handling with output...",
                "CRITICAL Bad output prohibited",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeTypedEndpointWithoutAnyErrors(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->handle_with_error = false;
        $endpoint->handle_with_output = self::$raw_input;
        $result = $endpoint->call(self::$raw_input);
        $this->assertEquals(self::$input, $result);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling with output...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }
}
