<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Endpoints;

use PhpTypeScriptApi\BackendTests\Common\ExampleBackendTestCase;

/**
 * @internal
 * @coversNothing
 */
final class EmptyEndpointTest extends ExampleBackendTestCase {
    public function testEmpty(): void {
        $result = $this->callBackend('empty', []);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame([], $result['result']);
    }
}
