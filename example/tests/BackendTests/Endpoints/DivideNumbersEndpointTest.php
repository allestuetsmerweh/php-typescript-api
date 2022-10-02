<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Endpoints;

use PhpTypeScriptApi\BackendTests\Common\ExampleBackendTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DivideNumbersEndpointTest extends ExampleBackendTestCase {
    public function testDividePositiveNumbersInvalid(): void {
        $result = $this->callBackend('divideNumbers', []);
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

    public function testDividePositiveNumbersIntegerResult(): void {
        $result = $this->callBackend('divideNumbers', ['dividend' => 6, 'divisor' => 3]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(2, $result['result']);
    }

    public function testDividePositiveNumbersFloatResult(): void {
        $result = $this->callBackend('divideNumbers', ['dividend' => 7, 'divisor' => 3]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(2.3333333333333333, $result['result']);
    }

    public function testDivideNegativePositive(): void {
        $result = $this->callBackend('divideNumbers', ['dividend' => -1, 'divisor' => 2]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(-0.5, $result['result']);
    }

    public function testDividePositiveNegative(): void {
        $result = $this->callBackend('divideNumbers', ['dividend' => 1, 'divisor' => -2]);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(-0.5, $result['result']);
    }

    public function testDivideByZero(): void {
        $result = $this->callBackend('divideNumbers', ['dividend' => 7, 'divisor' => 0]);
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
