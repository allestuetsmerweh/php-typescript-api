<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Endpoints;

use PhpTypeScriptApi\BackendTests\Common\ExampleBackendTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SquareRootTypedEndpointTest extends ExampleBackendTestCase {
    public function testSquareRootTyped(): void {
        $result = $this->callBackend('squareRootTyped', 9);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(3, $result['result']);
    }

    public function testSquareRootTypedFloat(): void {
        $result = $this->callBackend('squareRootTyped', 2);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(1.4142135623730951, $result['result']);
    }

    public function testSquareRootTypedZero(): void {
        $result = $this->callBackend('squareRootTyped', 0);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(0, $result['result']);
    }

    public function testSquareRootTypedImaginary(): void {
        $result = $this->callBackend('squareRootTyped', -1);
        $this->assertSame(400, $result['http_code']);
        $this->assertSame([
            'message' => '',
            'error' => [
                'type' => 'ValidationError',
                'validationErrors' => [
                    '.' => ['Value must not be negative'],
                ],
            ],
        ], $result['result']);
    }
}
