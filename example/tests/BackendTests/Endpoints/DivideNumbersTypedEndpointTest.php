<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Endpoints;

use PhpTypeScriptApi\BackendTests\Common\ExampleBackendTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DivideNumbersTypedEndpointTest extends ExampleBackendTestCase {
    public function testDivideTypedPositiveNumbersInvalid(): void {
        $result = $this->callBackend('divideNumbersTyped', []);
        $this->assertSame(400, $result['http_code']);
        $this->assertSame(
            [
                'message' => '',
                'error' => [
                    'type' => 'ValidationError',
                    'validationErrors' => [
                        'dividend' => [''],
                        'divisor' => [''],
                    ],
                ],
            ],
            $result['result']
        );
    }

    public function testDivideTypedPositiveNumbersIntegerResult(): void {
        $result = $this->callBackend('divideNumbersTyped', ['dividend' => 6, 'divisor' => 3]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(2, $result['result']);
    }

    public function testDivideTypedPositiveNumbersFloatResult(): void {
        $result = $this->callBackend('divideNumbersTyped', ['dividend' => 7, 'divisor' => 3]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(2.3333333333333333, $result['result']);
    }

    public function testDivideTypedNegativePositive(): void {
        $result = $this->callBackend('divideNumbersTyped', ['dividend' => -1, 'divisor' => 2]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(-0.5, $result['result']);
    }

    public function testDivideTypedPositiveNegative(): void {
        $result = $this->callBackend('divideNumbersTyped', ['dividend' => 1, 'divisor' => -2]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(-0.5, $result['result']);
    }

    public function testDivideTypedByZero(): void {
        $result = $this->callBackend('divideNumbersTyped', ['dividend' => 7, 'divisor' => 0]);
        $this->assertSame(400, $result['http_code']);
        $this->assertSame(
            [
                'message' => '',
                'error' => [
                    'type' => 'ValidationError',
                    'validationErrors' => [
                        'divisor' => ['Cannot divide by zero.'],
                    ],
                ],
            ],
            $result['result']
        );
    }
}
