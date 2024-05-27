<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Endpoints;

use PhpTypeScriptApi\BackendTests\Common\ExampleBackendTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SquareRootEndpointTest extends ExampleBackendTestCase {
    public function testSquareRoot(): void {
        $result = $this->callBackend('squareRoot', 9);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(3, $result['result']);
    }

    public function testSquareRootFloat(): void {
        $result = $this->callBackend('squareRoot', 2);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(1.4142135623730951, $result['result']);
    }

    public function testSquareRootZero(): void {
        $result = $this->callBackend('squareRoot', 0);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(0, $result['result']);
    }

    public function testSquareRootImaginary(): void {
        $result = $this->callBackend('squareRoot', -1);
        $this->assertSame(400, $result['http_code']);
        $this->assertSame([
            'message' => '',
            'error' => [
                'type' => 'ValidationError',
                'validationErrors' => [
                    '.' => [''],
                ],
            ],
        ], $result['result']);
    }
}
