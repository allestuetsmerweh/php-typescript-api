<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake;

/**
 * @template T
 */
class FakeMyArray {
    /** @param array<T> $array */
    public function __construct(
        public array $array = [],
    ) {
    }
}
