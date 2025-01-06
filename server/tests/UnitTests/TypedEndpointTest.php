<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
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

    /**
     * @return array<string, TypeNode>
     */
    public function testOnlyGetTemplateAliases(
        ?PhpDocNode $php_doc_node,
        ?ExtendsTagValueNode $previous_extends_node,
    ): array {
        return parent::getTemplateAliases($php_doc_node, $previous_extends_node);
    }

    /**
     * @param array<string, TypeNode> $template_aliases
     */
    public function testOnlyGetResolvedExtendsNode(
        ?PhpDocNode $php_doc_node,
        array $template_aliases,
    ): ?ExtendsTagValueNode {
        return parent::getResolvedExtendsNode($php_doc_node, $template_aliases);
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
 * @phpstan-type AliasedString string
 *
 * @extends FakeIntermediateGenericTypedEndpoint<int, AliasedString>
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

/**
 * @phpstan-type AliasedTypedEndpoint TypedEndpoint<mixed, mixed>
 *
 * @extends AliasedTypedEndpoint<int, string>
 */
class FakeAliasedTypedEndpoint extends TypedEndpoint {
    public static function getApiObjectClasses(): array {
        return [];
    }

    public static function getIdent(): string {
        return 'FakeAliasedTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        return 'test';
    }
}

/**
 * @phpstan-extends TypedEndpoint<int, string>
 */
class FakePhpstanExtendsTypedEndpoint extends TypedEndpoint {
    public static function getApiObjectClasses(): array {
        return [];
    }

    public static function getIdent(): string {
        return 'FakePhpstanExtendsTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        return 'test';
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
        $this->assertEquals(
            [
                'FakeNestedThing' => "{'id': number}",
                'FakeNamedThing' => "{'id': number, 'name': string, 'nested': FakeNestedThing}",
                'IsoDate' => "string",
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
        $this->assertEquals([
            'FakeNestedThing' => "{'id': number}",
            'FakeNamedThing' => "{'id': number, 'name': string, 'nested': FakeNestedThing}",
            'IsoDate' => "string",
        ], $endpoint->getNamedTsTypes());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeLeafGenericTypedEndpoint(): void {
        $endpoint = new FakeLeafGenericTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $this->assertEquals([
            'Input' => "{'in': In}",
            'AliasedString' => "string",
            'In' => "number",
        ], $endpoint->getNamedTsTypes());
        $this->assertSame("Input", $endpoint->getRequestTsType());
        $this->assertSame("AliasedString", $endpoint->getResponseTsType());
        $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
    }

    public function testFakeAliasedTypedEndpoint(): void {
        try {
            new FakeAliasedTypedEndpoint();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame(
                'PhpTypeScriptApi\Tests\UnitTests\FakeAliasedTypedEndpoint does not extend TypedEndpoint',
                $exc->getMessage(),
            );
            $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
        }
    }

    public function testFakePhpstanExtendsTypedEndpoint(): void {
        try {
            new FakePhpstanExtendsTypedEndpoint();
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame(
                'Could not parse type for PhpTypeScriptApi\Tests\UnitTests\FakePhpstanExtendsTypedEndpoint',
                $exc->getMessage(),
            );
            $this->assertSame([], $this->fakeLogHandler->getPrettyRecords());
        }
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
        $this->assertEquals(
            [
                'FakeNestedThing' => "{'id': number}",
                'FakeNamedThing' => "{'id': number, 'name': string, 'nested': FakeNestedThing}",
                '_PhpTypeScriptApi_PhpStan_IsoDate' => "string",
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

    public function testGetTemplateAliases(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $php_doc_node_0 = PhpStanUtils::parseDocComment(<<<'ZZZZZZZZZZ'
            /** */
            ZZZZZZZZZZ);
        $php_doc_node_1 = PhpStanUtils::parseDocComment(<<<'ZZZZZZZZZZ'
            /**
             * @template T
             */
            ZZZZZZZZZZ);
        $php_doc_node_2 = PhpStanUtils::parseDocComment(<<<'ZZZZZZZZZZ'
            /**
             * @template T
             * @template U
             */
            ZZZZZZZZZZ);
        $extends_1 = $this->getExtendsNode(['int']);
        $extends_2 = $this->getExtendsNode(['int', 'string']);
        $extends_3 = $this->getExtendsNode(['int', 'null', 'bool']);

        $this->assertSame([], $endpoint->testOnlyGetTemplateAliases(null, $extends_1));
        $this->assertSame([], $endpoint->testOnlyGetTemplateAliases(null, $extends_2));
        $this->assertSame([], $endpoint->testOnlyGetTemplateAliases(null, $extends_3));

        $this->assertSame([], $endpoint->testOnlyGetTemplateAliases($php_doc_node_0, null));

        $this->assertEquals([
            'T' => $this->getTypeNode('int'),
        ], $endpoint->testOnlyGetTemplateAliases($php_doc_node_1, $extends_1));
        $this->assertEquals([
            'T' => $this->getTypeNode('int'),
            'U' => $this->getTypeNode('string'),
        ], $endpoint->testOnlyGetTemplateAliases($php_doc_node_2, $extends_2));

        try {
            $endpoint->testOnlyGetTemplateAliases($php_doc_node_0, $extends_1);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 0 generic arguments, but got 'FakeGeneric<int>'",
                $th->getMessage()
            );
        }

        try {
            $endpoint->testOnlyGetTemplateAliases($php_doc_node_1, $extends_2);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 1 generic arguments, but got 'FakeGeneric<int, string>'",
                $th->getMessage()
            );
        }

        try {
            $endpoint->testOnlyGetTemplateAliases($php_doc_node_2, $extends_3);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 2 generic arguments, but got 'FakeGeneric<int, null, bool>'",
                $th->getMessage()
            );
        }

        try {
            $endpoint->testOnlyGetTemplateAliases($php_doc_node_2, $extends_1);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 2 generic arguments, but got 'FakeGeneric<int>'",
                $th->getMessage()
            );
        }

        try {
            $endpoint->testOnlyGetTemplateAliases($php_doc_node_1, null);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 1 generic arguments, but got '<>'",
                $th->getMessage()
            );
        }
    }

    public function testGetResolvedExtendsNode(): void {
        $endpoint = new FakeTypedEndpoint();
        $endpoint->setLogger($this->fakeLogger);
        $php_doc_node_1 = fn () => PhpStanUtils::parseDocComment(<<<'ZZZZZZZZZZ'
            /**
             * @extends FakeGeneric<T>
             */
            ZZZZZZZZZZ);
        $php_doc_node_2 = fn () => PhpStanUtils::parseDocComment(<<<'ZZZZZZZZZZ'
            /**
             * @extends FakeGeneric<T, U>
             */
            ZZZZZZZZZZ);
        $php_doc_node_3 = fn () => PhpStanUtils::parseDocComment(<<<'ZZZZZZZZZZ'
            /**
             * @extends FakeGeneric<bool, array<T>, U>
             */
            ZZZZZZZZZZ);
        $aliases_0 = [];
        $aliases_1 = ['T' => $this->getTypeNode('float')];
        $aliases_2 = [
            'U' => $this->getTypeNode('string'),
            'T' => $this->getTypeNode('int'),
        ];
        $extends_1_1 = $this->getExtendsNode(['float']);
        $extends_2_2 = $this->getExtendsNode(['int', 'string']);
        $extends_3_2 = $this->getExtendsNode(['bool', 'array<int>', 'string']);
        $extends_1_0 = $this->getExtendsNode(['T']);
        $extends_2_1 = $this->getExtendsNode(['float', 'U']);
        $extends_3_1 = $this->getExtendsNode(['bool', 'array<float>', 'U']);
        $extends_2_0 = $this->getExtendsNode(['T', 'U']);
        $extends_3_0 = $this->getExtendsNode(['bool', 'array<T>', 'U']);

        $this->assertSame(null, $endpoint->testOnlyGetResolvedExtendsNode(null, $aliases_0));
        $this->assertSame(null, $endpoint->testOnlyGetResolvedExtendsNode(null, $aliases_1));
        $this->assertSame(null, $endpoint->testOnlyGetResolvedExtendsNode(null, $aliases_2));

        $this->assertEquals($extends_1_1, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_1(), $aliases_1));
        $this->assertEquals($extends_2_2, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_2(), $aliases_2));
        $this->assertEquals($extends_3_2, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_3(), $aliases_2));

        $this->assertEquals($extends_1_0, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_1(), $aliases_0));
        $this->assertEquals($extends_2_1, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_2(), $aliases_1));
        $this->assertEquals($extends_3_1, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_3(), $aliases_1));
        $this->assertEquals($extends_2_0, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_2(), $aliases_0));
        $this->assertEquals($extends_3_0, $endpoint->testOnlyGetResolvedExtendsNode($php_doc_node_3(), $aliases_0));
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
