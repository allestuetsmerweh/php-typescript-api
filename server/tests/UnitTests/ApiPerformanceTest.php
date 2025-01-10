<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Api;
use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\IsoDateTime;
use PhpTypeScriptApi\PhpStan\IsoTime;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use PhpTypeScriptApi\TypedEndpoint;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

class FakeApiPerfTestSimpleEndpoint extends Endpoint {
    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\ObjectField(['field_structure' => [
            'simple' => new FieldTypes\BooleanField(),
        ]]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\ObjectField(['field_structure' => [
            'simple' => new FieldTypes\BooleanField(),
        ]]);
    }

    public function runtimeSetup(): void {
        // intentionally blank
    }

    protected function handle(mixed $input): mixed {
        return $input;
    }

    /** @return array{simple: bool} */
    public function getFakeRequest(): array {
        return ['simple' => true];
    }
}

class FakeApiPerfTestComplexEndpoint extends Endpoint {
    public function getResponseField(): FieldTypes\Field {
        return $this->getComplexField(1);
    }

    public function getRequestField(): FieldTypes\Field {
        return $this->getComplexField(1);
    }

    /** @param non-negative-int $complexity */
    protected function getComplexField(int $complexity = 1): FieldTypes\Field {
        $sub_field = $complexity === 0
            ? new FieldTypes\Field([])
            : $this->getComplexField($complexity - 1);
        return new FieldTypes\ObjectField(['field_structure' => [
            'array' => new FieldTypes\ArrayField(['item_field' => $sub_field]),
            'boolean' => new FieldTypes\BooleanField([]),
            'choice' => new FieldTypes\ChoiceField(['field_map' => [
                'accept' => $sub_field,
            ]]),
            'date' => new FieldTypes\DateField([]),
            'dateTime' => new FieldTypes\DateTimeField([]),
            'dict' => new FieldTypes\DictField(['item_field' => $sub_field]),
            'enum' => new FieldTypes\EnumField(['allowed_values' => ['foo', 'bar']]),
            'integer' => new FieldTypes\IntegerField([]),
            'number' => new FieldTypes\NumberField([]),
            'string' => new FieldTypes\StringField([]),
            'time' => new FieldTypes\TimeField([]),
        ]]);
    }

    public function runtimeSetup(): void {
        // intentionally blank
    }

    protected function handle(mixed $input): mixed {
        return $input;
    }

    /**
     * @param non-negative-int $complexity
     *
     * @return array<string, mixed>
     */
    protected function getComplexFieldFakeValue(int $complexity = 1): array {
        $sub_value = $complexity === 0
            ? 'mixed'
            : $this->getComplexFieldFakeValue($complexity - 1);
        return [
            'array' => [$sub_value, $sub_value, $sub_value],
            'boolean' => true,
            'choice' => ['accept' => $sub_value],
            'date' => '2020-03-16',
            'dateTime' => '2020-03-16 12:34:56',
            'dict' => ['foo' => $sub_value, 'bar' => $sub_value],
            'enum' => 'foo',
            'integer' => 123,
            'number' => 3.14,
            'string' => 'text',
            'time' => '12:34:56',
        ];
    }

    /** @return array<string, mixed> */
    public function getFakeRequest(): array {
        return $this->getComplexFieldFakeValue(1);
    }
}

/**
 * @extends TypedEndpoint<array{simple: bool}, array{simple: bool}>
 */
class FakeApiPerfTestSimpleTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        return $input;
    }

    /** @return array{simple: bool} */
    public function getFakeRequest(): array {
        return ['simple' => true];
    }
}

/**
 * @phpstan-type ComplexField0 array{
 *   array: array<mixed>,
 *   boolean: bool,
 *   choice: false|mixed,
 *   date: IsoDate,
 *   dateTime: IsoDateTime,
 *   dict: array<string, mixed>,
 *   enum: 'foo'|'bar',
 *   integer: int,
 *   number: float,
 *   string: string,
 *   time: IsoTime,
 * }
 * @phpstan-type ComplexField1 array{
 *   array: array<ComplexField0>,
 *   boolean: bool,
 *   choice: false|ComplexField0,
 *   date: IsoDate,
 *   dateTime: IsoDateTime,
 *   dict: array<string, ComplexField0>,
 *   enum: 'foo'|'bar',
 *   integer: int,
 *   number: float,
 *   string: string,
 *   time: IsoTime,
 * }
 *
 * @extends TypedEndpoint<ComplexField1, ComplexField1>
 */
class FakeApiPerfTestComplexTypedEndpoint extends TypedEndpoint {
    public function configure(): void {
        $this->phpStanUtils->registerApiObject(IsoDate::class);
        $this->phpStanUtils->registerApiObject(IsoDateTime::class);
        $this->phpStanUtils->registerApiObject(IsoTime::class);
    }

    protected function handle(mixed $input): mixed {
        return $input;
    }

    /** @return array<string, mixed> */
    protected function getComplexFieldFakeValue1(): array {
        $sub_value = $this->getComplexFieldFakeValue0();
        return [
            'array' => [$sub_value, $sub_value, $sub_value],
            'boolean' => true,
            'choice' => $sub_value,
            'date' => '2020-03-16',
            'dateTime' => '2020-03-16 12:34:56',
            'dict' => ['foo' => $sub_value, 'bar' => $sub_value],
            'enum' => 'foo',
            'integer' => 123,
            'number' => 3.14,
            'string' => 'text',
            'time' => '12:34:56',
        ];
    }

    /** @return array<string, mixed> */
    protected function getComplexFieldFakeValue0(): array {
        $sub_value = 'mixed';
        return [
            'array' => [$sub_value, $sub_value, $sub_value],
            'boolean' => true,
            'choice' => false,
            'date' => '2020-03-16',
            'dateTime' => '2020-03-16 12:34:56',
            'dict' => ['foo' => $sub_value, 'bar' => $sub_value],
            'enum' => 'foo',
            'integer' => 123,
            'number' => 3.14,
            'string' => 'text',
            'time' => '12:34:56',
        ];
    }

    /** @return array<string, mixed> */
    public function getFakeRequest(): array {
        return $this->getComplexFieldFakeValue1();
    }
}

function threshold(int|float ...$measurements): float {
    $sum = floatval(array_sum($measurements));
    $num = floatval(count($measurements));
    return $sum * 2 / $num;
}

/**
 * Note: Thresholds must be provided in Milliseconds (ms).
 *
 * @phpstan-type ApiConfig array{0: bool, 1: bool, 2: bool}
 *
 * @internal
 *
 * @coversNothing
 */
final class ApiPerformanceTest extends UnitTestCase {
    /** @return array<string, array{0: ApiConfig}> */
    public static function apiConfigProvider(): array {
        $typed_cases = [false, true];
        $complex_cases = [false, true];
        $direct_cases = [false, true];
        $api_configs = [];
        foreach ($typed_cases as $typed_case) {
            foreach ($complex_cases as $complex_case) {
                foreach ($direct_cases as $direct_case) {
                    $pretty_typed = $typed_case ? 'typed' : 'normal';
                    $pretty_complex = $complex_case ? 'complex' : 'simple';
                    $pretty_direct = $direct_case ? 'direct' : 'indirect';
                    $name = "{$pretty_typed} {$pretty_complex} {$pretty_direct}";
                    $api_config = [
                        $typed_case,
                        $complex_case,
                        $direct_case,
                    ];
                    $api_configs[$name] = [$api_config];
                }
            }
        }
        return $api_configs;
    }

    /** @param ApiConfig $api_config */
    #[DataProvider('apiConfigProvider')]
    public function testGetFakeApiLatency(array $api_config): void {
        [$duration_ms, $fake_api] = $this->runPerformance(function () use ($api_config) {
            return $this->getFakeApi(100_000, $api_config);
        });
        // See note in doc comment of class.
        $thresholds = [
            'normal simple indirect' => threshold(329, 214, 184),
            'normal simple direct' => threshold(150, 439, 120),
            'normal complex indirect' => threshold(172, 160, 294),
            'normal complex direct' => threshold(136, 134, 132),
            'typed simple indirect' => threshold(192, 154, 155),
            'typed simple direct' => threshold(202, 188, 209),
            'typed complex indirect' => threshold(162, 155, 167),
            'typed complex direct' => threshold(251, 188, 212),
        ];
        $this->assertLessThan(
            $thresholds[$this->dataName()],
            $duration_ms,
            'getFakeApi latency',
        );
        $this->assertInstanceOf(Api::class, $fake_api);
    }

    /** @param ApiConfig $api_config */
    #[DataProvider('apiConfigProvider')]
    public function testGetTypeScriptDefinitionLatency(array $api_config): void {
        $fake_api = $this->getFakeApi(100, $api_config);

        [$duration_ms, $ts_type] = $this->runPerformance(function () use ($fake_api) {
            return $fake_api->getTypeScriptDefinition('FakeApi');
        });
        // See note in doc comment of class.
        $thresholds = [
            'normal simple indirect' => threshold(6, 3, 3),
            'normal simple direct' => threshold(3, 3, 5),
            'normal complex indirect' => threshold(54, 45, 53),
            'normal complex direct' => threshold(49, 44, 48),
            'typed simple indirect' => threshold(62, 55, 58),
            'typed simple direct' => threshold(56, 53, 53),
            'typed complex indirect' => threshold(485, 424, 422),
            'typed complex direct' => threshold(495, 416, 485),
        ];
        $this->assertLessThan(
            $thresholds[$this->dataName()],
            $duration_ms,
            'getTypeScriptDefinition latency',
        );
        $this->assertStringContainsString('fakeEndpoint0', $ts_type);
    }

    /** @param ApiConfig $api_config */
    #[DataProvider('apiConfigProvider')]
    public function testGetResponseLatency(array $api_config): void {
        $fake_api = $this->getFakeApi(10_000, $api_config);

        [$duration_ms, $endpoint] = $this->runPerformance(function () use ($fake_api) {
            return $fake_api->getEndpointByName('fakeEndpoint0');
        });
        // See note in doc comment of class.
        $thresholds = [
            'normal simple indirect' => 1,
            'normal simple direct' => 1,
            'normal complex indirect' => 1,
            'normal complex direct' => 1,
            'typed simple indirect' => 1,
            'typed simple direct' => 1,
            'typed complex indirect' => 1,
            'typed complex direct' => 1,
        ];
        $this->assertLessThan(
            $thresholds[$this->dataName()],
            $duration_ms,
            'getResponse latency',
        );
        if (
            !($endpoint instanceof FakeApiPerfTestSimpleEndpoint)
            && !($endpoint instanceof FakeApiPerfTestComplexEndpoint)
            && !($endpoint instanceof FakeApiPerfTestSimpleTypedEndpoint)
            && !($endpoint instanceof FakeApiPerfTestComplexTypedEndpoint)
        ) {
            throw new \Exception("Must be one of the PerfTest endpoints!");
        }
        $server = ['PATH_INFO' => '/fakeEndpoint0'];
        $valid_request = $endpoint->getFakeRequest();
        $invalid_request = [...$valid_request, 'invalid' => true];
        $valid_body = json_encode($valid_request);
        $invalid_body = json_encode($invalid_request);
        assert($valid_body);
        assert($invalid_body);
        $valid_request = new Request([], [], [], [], [], $server, $valid_body);
        $invalid_request = new Request([], [], [], [], [], $server, $invalid_body);

        [$duration_ms, [$valid_response, $invalid_response]] =
            $this->runPerformance(function () use ($fake_api, $valid_request, $invalid_request) {
                $valid_response = $fake_api->getResponse($valid_request);
                $invalid_response = $fake_api->getResponse($invalid_request);
                for ($i = 0; $i < 20; $i++) {
                    $fake_api->getResponse($valid_request);
                    $fake_api->getResponse($invalid_request);
                }
                return [$valid_response, $invalid_response];
            });
        $this->assertSame($valid_body, $valid_response->getContent());
        $this->assertStringContainsString('error', "{$invalid_response->getContent()}");
        // See note in doc comment of class.
        $thresholds = [
            'normal simple indirect' => threshold(7, 8, 7),
            'normal simple direct' => threshold(6, 6, 7),
            'normal complex indirect' => threshold(30, 29, 74),
            'normal complex direct' => threshold(30, 29, 84),
            'typed simple indirect' => threshold(22, 21, 60),
            'typed simple direct' => threshold(23, 21, 30),
            'typed complex indirect' => threshold(494, 490, 521),
            'typed complex direct' => threshold(492, 494, 533),
        ];
        $this->assertLessThan(
            $thresholds[$this->dataName()],
            $duration_ms,
            'getResponse latency',
        );
    }

    /**
     * @template T
     *
     * @param callable(): T $callable
     *
     * @return array{0: float, 1: T}
     */
    protected function runPerformance(callable $callable): array {
        gc_collect_cycles();
        $before = microtime(true);
        $result = $callable();
        $duration_s = microtime(true) - $before;
        $duration_ms = $duration_s * 1000;
        return [$duration_ms, $result];
    }

    /** @param ApiConfig $api_config */
    protected function getFakeApi(int $num_endpoints, array $api_config): Api {
        [$typed, $complex, $direct] = $api_config;
        $class = $typed
            ? ($complex
                ? FakeApiPerfTestComplexTypedEndpoint::class
                : FakeApiPerfTestSimpleTypedEndpoint::class)
            : ($complex
                ? FakeApiPerfTestComplexEndpoint::class
                : FakeApiPerfTestSimpleEndpoint::class);
        if ($direct) {
            $create_arg = fn () => new $class();
        } else {
            $create_arg = fn () => fn () => new $class();
        }

        $fake_api = new Api();
        for ($i = 0; $i < $num_endpoints; $i++) {
            $fake_api->registerEndpoint(
                "fakeEndpoint{$i}",
                $create_arg(),
            );
        }
        return $fake_api;
    }
}
