<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PhpTypeScriptApi\PhpStan\TypeScriptObjectTypeNode;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\TypeScriptObjectTypeNode
 */
final class TypeScriptObjectTypeNodeTest extends UnitTestCase {
    public function testObjectToString(): void {
        $node = new TypeScriptObjectTypeNode([
            new ArrayShapeItemNode(new IdentifierTypeNode('foo'), false, new IdentifierTypeNode('int')),
            new ObjectShapeItemNode(new IdentifierTypeNode('bar'), true, new IdentifierTypeNode('string')),
        ]);
        $this->assertSame('{foo: int, bar?: string}', "{$node}");
    }

    public function testEmptyObjectToString(): void {
        $node = new TypeScriptObjectTypeNode([]);
        $this->assertSame('Record<string, never>', "{$node}");
    }
}
