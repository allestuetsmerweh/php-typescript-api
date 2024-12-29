<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\ValidationResultNode;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\ValidationResultNode
 */
final class ValidationResultNodeTest extends UnitTestCase {
    public function testValidValidationResultNode(): void {
        $node = ValidationResultNode::valid();
        $this->assertSame('✅', "{$node}");
        $this->assertTrue($node->isValid());
        $this->assertSame([], $node->getErrors());
    }

    public function testInvalidValidationResultNode(): void {
        $node = ValidationResultNode::error('error');
        $this->assertSame('{".":["error"]}', "{$node}");
        $this->assertFalse($node->isValid());
        $this->assertSame(['.' => ['error']], $node->getErrors());
    }
}
