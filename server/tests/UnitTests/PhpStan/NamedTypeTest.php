<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\NamedType;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/** @extends NamedType<int> */
class NamedInt extends NamedType {
}

/** @extends NamedType<array{foo: int, bar?: string}> */
class NamedObject extends NamedType {
}

class AllegedlyNamedSomething {
}

class AllegedlyNamedAnotherThing extends \DateTime {
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\NamedType
 */
final class NamedTypeTest extends UnitTestCase {
    public function testCannotInstantiate(): void {
        try {
            new NamedType(null);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                '`NamedType`s shall not actually be instantiated',
                $th->getMessage(),
            );
        }
    }
}
