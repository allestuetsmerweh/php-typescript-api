<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PhpTypeScriptApi\PhpStan\TypeScriptDictTypeNode;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\TypeScriptDictTypeNode
 */
final class TypeScriptDictTypeNodeTest extends UnitTestCase {
    public function testCannotInstantiate(): void {
        $node = new TypeScriptDictTypeNode(
            new IdentifierTypeNode('int'),
            new IdentifierTypeNode('string'),
        );
        $this->assertSame('{[key: int]: string}', "{$node}");
    }
}
