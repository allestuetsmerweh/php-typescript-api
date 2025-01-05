<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\BackendTests\Endpoints;

use PhpTypeScriptApi\BackendTests\Common\ExampleBackendTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CombineDateTimeTypedEndpointTest extends ExampleBackendTestCase {
    public function testCombineDateTimeTyped(): void {
        $result = $this->callBackend('combineDateTimeTyped', [
            'date' => '2025-01-01',
            'time' => '13:27:35',
        ]);
        $this->assertSame('', $result['error']);
        $this->assertSame(200, $result['http_code']);
        $this->assertSame(['dateTime' => '2025-01-01 13:27:35'], $result['result']);
    }
}
