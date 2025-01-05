<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\ValidationResultNode;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\ValidationResultNode
 */
final class ValidationResultNodeTest extends UnitTestCase {
    public function testValidValidationResultNode(): void {
        $node = ValidationResultNode::valid('test_value');
        $this->assertSame('✅ "test_value"', "{$node}");
        $this->assertTrue($node->isValid());
        $this->assertSame('test_value', $node->getValue());
        $this->assertSame([], $node->getErrors());
    }

    public function testComplexValidValidationResultNode(): void {
        $node = ValidationResultNode::valid(new IsoDate('2024-12-24'));
        $this->assertSame('✅ {"date":"2024-12-24 00:00:00.000000","timezone_type":3,"timezone":"UTC"}', "{$node}");
        $this->assertTrue($node->isValid());
        $this->assertEquals(new IsoDate('2024-12-24'), $node->getValue());
        $this->assertSame([], $node->getErrors());
    }

    public function testInvalidValidationResultNode(): void {
        $node = ValidationResultNode::error('error');
        $node->setValue('dummy');
        $this->assertSame('🚫 {".":["error"]}', "{$node}");
        $this->assertFalse($node->isValid());
        $this->assertSame('dummy', $node->getValue());
        $this->assertSame(['.' => ['error']], $node->getErrors());
    }
}
