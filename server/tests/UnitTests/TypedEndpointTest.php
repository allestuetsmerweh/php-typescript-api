<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use PhpTypeScriptApi\TypedEndpoint;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-import-type NamespaceAliases from PhpStanUtils
 *
 * @phpstan-type FakeNestedThing array{id: int}
 * @phpstan-type FakeNamedThing array{id: int, name: string, nested: FakeNestedThing}
 * @phpstan-type FakeInput array{
 *   mapping: array<string, number>,
 *   named: FakeNamedThing,
 *   date: IsoDate,
 * }
 * @phpstan-type FakeOutput array{
 *   mapping: array<string, number>,
 *   named: FakeNamedThing,
 *   date: IsoDate,
 * }
 *
 * @extends TypedEndpoint<FakeInput, ?FakeOutput>
 */
class FakeTypedEndpoint extends TypedEndpoint {
    /** @var ?FakeInput */
    public mixed $handled_with_input = null;
    /** @var ?FakeOutput */
    public mixed $handle_with_output = null;
    public bool $ran_runtime_setup = false;

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
 * @template CustomIn = never
 *
 * @phpstan-type Input array{in: In, custom?: CustomIn}
 *
 * @extends TypedEndpoint<Input, Out>
 */
abstract class FakeIntermediateGenericTypedEndpoint extends TypedEndpoint {
}

/**
 * @phpstan-type AliasedString string
 *
 * @extends FakeIntermediateGenericTypedEndpoint<int, AliasedString, int>
 */
class FakeLeafGenericTypedEndpoint extends FakeIntermediateGenericTypedEndpoint {
    protected function handle(mixed $input): mixed {
        return 'test';
    }
}

/**
 * @phpstan-type AliasedString string
 *
 * @extends FakeIntermediateGenericTypedEndpoint<int, AliasedString>
 */
class FakeLeafPartialGenericTypedEndpoint extends FakeIntermediateGenericTypedEndpoint {
    protected function handle(mixed $input): mixed {
        return 'test';
    }
}

class FakeTransitiveTypedEndpoint extends FakeTypedEndpoint {
}

/**
 * @internal
 *
 * @coversNothing
 */
class FakeTransitiveBogusTypedEndpoint extends FakeTypedEndpoint {
}

/**
 * @phpstan-type AliasedTypedEndpoint TypedEndpoint<mixed, mixed>
 *
 * @extends AliasedTypedEndpoint<int, string>
 */
class FakeAliasedTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        return 'test';
    }
}

/**
 * @phpstan-extends TypedEndpoint<int, string>
 */
class FakePhpstanExtendsTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        return 'test';
    }
}

/**
 * @phpstan-import-type FakeNamedThing from FakeTypedEndpoint
 *
 * @extends TypedEndpoint<FakeNamedThing, string>
 */
class FakeMissingHiddenImportTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        return 'test';
    }
}

/**
 * Note: At the moment, FakeNestedThing must be manually imported as well.
 *
 * @phpstan-import-type FakeNestedThing from FakeTypedEndpoint
 * @phpstan-import-type FakeNamedThing from FakeTypedEndpoint
 *
 * @phpstan-type FakeInputAbsolute array{
 *   mapping: array<string, number>,
 *   named: FakeNamedThing,
 *   date: \PhpTypeScriptApi\PhpStan\IsoDate,
 * }
 * @phpstan-type FakeOutputAbsolute array{
 *   mapping: array<string, number>,
 *   named: FakeNamedThing,
 *   date: \PhpTypeScriptApi\PhpStan\IsoDate,
 * }
 *
 * @extends \PhpTypeScriptApi\TypedEndpoint<FakeInputAbsolute, FakeOutputAbsolute>
 */
class FakeTypedEndpointWithErrors extends TypedEndpoint {
    public bool $handle_with_throttling = false;
    public bool $handle_with_error = false;
    public bool $handle_with_http_error = false;
    public bool $handle_with_validation_error = false;
    /** @var ?FakeOutputAbsolute */
    public mixed $handle_with_output = null;

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
        // We want to be able to return invalid values here to provoke errors.
        // @phpstan-ignore return.type
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
     *     date: IsoDate,
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
        $endpoint->handle_with_output = self::$input;
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

    public function testFakeTypedEndpointMetadata(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeInput", $endpoint->getRequestTsType());
        $this->assertSame("(PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeOutput | null)", $endpoint->getResponseTsType());
        $this->assertEquals(
            [
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing' => "{'id': number}",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing' => "{'id': number, 'name': string, 'nested': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing}",
                'PhpTypeScriptApi_PhpStan_IsoDate' => "string",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeInput' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing, 'date': PhpTypeScriptApi_PhpStan_IsoDate}",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeOutput' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing, 'date': PhpTypeScriptApi_PhpStan_IsoDate}",
            ],
            $endpoint->getNamedTsTypes(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTransitiveTypedEndpointMetadata(): void {
        $endpoint = new FakeTransitiveTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeInput", $endpoint->getRequestTsType());
        $this->assertSame("(PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeOutput | null)", $endpoint->getResponseTsType());
        $this->assertEquals([
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing' => "{'id': number}",
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing' => "{'id': number, 'name': string, 'nested': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing}",
            'PhpTypeScriptApi_PhpStan_IsoDate' => "string",
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeInput' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing, 'date': PhpTypeScriptApi_PhpStan_IsoDate}",
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeOutput' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing, 'date': PhpTypeScriptApi_PhpStan_IsoDate}",
        ], $endpoint->getNamedTsTypes());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTransitiveBogusTypedEndpointMetadata(): void {
        $endpoint = new FakeTransitiveBogusTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeInput", $endpoint->getRequestTsType());
        $this->assertSame("(PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeOutput | null)", $endpoint->getResponseTsType());
        $this->assertEquals([
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing' => "{'id': number}",
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing' => "{'id': number, 'name': string, 'nested': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing}",
            'PhpTypeScriptApi_PhpStan_IsoDate' => "string",
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeInput' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing, 'date': PhpTypeScriptApi_PhpStan_IsoDate}",
            'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeOutput' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing, 'date': PhpTypeScriptApi_PhpStan_IsoDate}",
        ], $endpoint->getNamedTsTypes());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeLeafGenericTypedEndpointMetadata(): void {
        $endpoint = new FakeLeafGenericTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_Input", $endpoint->getRequestTsType());
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_Out", $endpoint->getResponseTsType());
        $this->assertEquals([
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_Input' => "{'in': PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_In, 'custom'?: PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_CustomIn}",
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_Out' => 'PhpTypeScriptApi_Tests_UnitTests_FakeLeafGenericTypedEndpoint_AliasedString',
            'PhpTypeScriptApi_Tests_UnitTests_FakeLeafGenericTypedEndpoint_AliasedString' => "string",
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_In' => "number",
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpoint9e8898dd171e16fef23859974ed8e6a2_CustomIn' => "number",
        ], $endpoint->getNamedTsTypes());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeLeafPartialGenericTypedEndpointMetadata(): void {
        $endpoint = new FakeLeafPartialGenericTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_Input", $endpoint->getRequestTsType());
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_Out", $endpoint->getResponseTsType());
        $this->assertEquals([
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_Input' => "{'in': PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_In, 'custom'?: PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_CustomIn}",
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_Out' => 'PhpTypeScriptApi_Tests_UnitTests_FakeLeafPartialGenericTypedEndpoint_AliasedString',
            'PhpTypeScriptApi_Tests_UnitTests_FakeLeafPartialGenericTypedEndpoint_AliasedString' => "string",
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_In' => "number",
            'PhpTypeScriptApi_Tests_UnitTests_FakeIntermediateGenericTypedEndpointf70527c952d0bbb01eb9faa41d61ad07_CustomIn' => "never",
        ], $endpoint->getNamedTsTypes());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeAliasedTypedEndpointMetadata(): void {
        $endpoint = new FakeAliasedTypedEndpoint();
        try {
            $endpoint->parseType();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame(
                'PhpTypeScriptApi\Tests\UnitTests\FakeAliasedTypedEndpoint does not extend PhpTypeScriptApi\TypedEndpoint',
                $exc->getMessage(),
            );
            $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakePhpstanExtendsTypedEndpointMetadata(): void {
        $endpoint = new FakePhpstanExtendsTypedEndpoint();
        try {
            $endpoint->getNamedTsTypes();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame(
                'PhpTypeScriptApi\Tests\UnitTests\FakePhpstanExtendsTypedEndpoint must provide two generics to TypedEndpoint, provided TypedEndpoint<>',
                $exc->getMessage(),
            );
            $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakeMissingHiddenImportTypedEndpointMetadata(): void {
        $endpoint = new FakeMissingHiddenImportTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeMissingHiddenImportTypedEndpoint_FakeNamedThing", $endpoint->getRequestTsType());
        $this->assertSame("string", $endpoint->getResponseTsType());
        $this->assertEquals(
            [
                'PhpTypeScriptApi_Tests_UnitTests_FakeMissingHiddenImportTypedEndpoint_FakeNamedThing' => "PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing' => "{'id': number, 'name': string, 'nested': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing}",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing' => "{'id': number}",
            ],
            $endpoint->getNamedTsTypes(),
        );
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeTypedEndpointWithErrorsMetadata(): void {
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpointWithErrors_FakeInputAbsolute", $endpoint->getRequestTsType());
        $this->assertSame("PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpointWithErrors_FakeOutputAbsolute", $endpoint->getResponseTsType());
        $this->assertEquals(
            [
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpointWithErrors_FakeInputAbsolute' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpointWithErrors_FakeNamedThing, 'date': _PhpTypeScriptApi_PhpStan_IsoDate}",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpointWithErrors_FakeOutputAbsolute' => "{'mapping': {[key: string]: number}, 'named': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpointWithErrors_FakeNamedThing, 'date': _PhpTypeScriptApi_PhpStan_IsoDate}",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpointWithErrors_FakeNamedThing' => 'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing',
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNamedThing' => "{'id': number, 'name': string, 'nested': PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing}",
                'PhpTypeScriptApi_Tests_UnitTests_FakeTypedEndpoint_FakeNestedThing' => "{'id': number}",
                '_PhpTypeScriptApi_PhpStan_IsoDate' => "string",
            ],
            $endpoint->getNamedTsTypes(),
        );
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

    public function testFakeTypedEndpointRuntimeSetupFallback(): void {
        global $_GET, $_POST;
        $endpoint = new FakeTypedEndpointWithErrors();
        $endpoint->setLogger($this->fakeLogger);
        $endpoint->setup();
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
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
                "NOTICE Throttled user request",
            ], $this->fakeLogHandler->getPrettyRecords());
        }
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
                "NOTICE Bad user request",
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
                "NOTICE HTTP error 418",
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
                "NOTICE Bad user request",
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
        $endpoint->handle_with_output = self::$input;
        $result = $endpoint->call(self::$raw_input);
        $this->assertEquals(self::$input, $result);
        $this->assertSame([
            "INFO Valid user request",
            "INFO Handling with output...",
            "INFO Valid user response",
        ], $this->fakeLogHandler->getPrettyRecords());
    }

    /** @param array<string> $type_strings */
    protected function getExtendsNode(array $type_strings): ExtendsTagValueNode {
        $generic_node = new GenericTypeNode(
            new IdentifierTypeNode('FakeGeneric'),
            array_map(
                fn ($type_string) => $this->getTypeNode($type_string),
                $type_strings,
            ),
            array_map(
                fn () => GenericTypeNode::VARIANCE_INVARIANT,
                $type_strings,
            ),
        );
        return new ExtendsTagValueNode($generic_node, '');
    }
}
