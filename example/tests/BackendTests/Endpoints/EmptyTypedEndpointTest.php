<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Endpoints;

use PhpTypeScriptApi\BackendTests\Common\ExampleBackendTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class EmptyTypedEndpointTest extends ExampleBackendTestCase {
    public function testEmptyTyped(): void {
        $result = $this->callBackend('emptyTyped', []);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame([], $result['result']);
    }
}
