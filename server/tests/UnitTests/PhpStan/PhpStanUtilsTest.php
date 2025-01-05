<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\ApiObjectInterface;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @phpstan-type AliasedInt int
 */
class FakePhpStanUtilsTypedEndpoint {
}

/**
 * @implements ApiObjectInterface<'foo'>
 */
class FakeApiObject implements ApiObjectInterface {
    public function data(): mixed {
        return 'foo';
    }

    public static function fromData(mixed $data): FakeApiObject {
        if ($data !== 'foo') {
            throw new \InvalidArgumentException("FakeApiObject must be foo");
        }
        return new FakeApiObject();
    }
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\PhpStanUtils
 */
final class PhpStanUtilsTest extends UnitTestCase {
    public function testApiObjectResolveFull(): void {
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveApiObjectClass(FakeApiObject::class)?->getName(),
        );
    }

    public function testApiObjectResolveFullyQualified(): void {
        $class_name = FakeApiObject::class;
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveApiObjectClass("\\{$class_name}")?->getName(),
        );
    }

    public function testApiObjectResolveShort(): void {
        PhpStanUtils::registerApiObject(FakeApiObject::class);

        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveApiObjectClass('FakeApiObject')?->getName(),
        );
    }

    public function testApiObjectResolveInvalid(): void {
        $this->assertNull(PhpStanUtils::resolveApiObjectClass('Invalid'));
    }

    public function testApiObjectResolveNonApiObject(): void {
        $this->assertNull(PhpStanUtils::resolveApiObjectClass(PhpStanUtilsTest::class));
    }

    public function testApiObjectTypeNodeFull(): void {
        $node = PhpStanUtils::getApiObjectTypeNode(FakeApiObject::class);
        $this->assertSame("'foo'", "{$node}");
    }

    public function testApiObjectTypeNodeFullyQualified(): void {
        $class_name = FakeApiObject::class;
        $node = PhpStanUtils::getApiObjectTypeNode("\\{$class_name}");
        $this->assertSame("'foo'", "{$node}");
    }

    public function testApiObjectTypeNodeShort(): void {
        PhpStanUtils::registerApiObject(FakeApiObject::class);

        $node = PhpStanUtils::getApiObjectTypeNode('FakeApiObject');
        $this->assertSame("'foo'", "{$node}");
    }

    public function testApiObjectTypeNodeInvalid(): void {
        $this->assertNull(PhpStanUtils::getApiObjectTypeNode('Invalid'));
    }

    public function testApiObjectTypeNodeNonApiObject(): void {
        $this->assertNull(PhpStanUtils::getApiObjectTypeNode(PhpStanUtilsTest::class));
    }

    public function testParseValidDocComment(): void {
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @param string $arg0
             * @return int
             */
            ZZZZZZZZZZ;

        $phpDocNode = PhpStanUtils::parseDocComment($comment);

        $this->assertSame('string', "{$phpDocNode->getParamTagValues()[0]->type}");
        $this->assertSame('int', "{$phpDocNode->getReturnTagValues()[0]->type}");
    }

    public function testParseEmptyDocComment(): void {
        $phpDocNode = PhpStanUtils::parseDocComment('/** Empty */');

        $this->assertCount(0, $phpDocNode->getParamTagValues());
        $this->assertCount(0, $phpDocNode->getReturnTagValues());
    }

    public function testParseInexistentDocComment(): void {
        try {
            PhpStanUtils::parseDocComment(false);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('Cannot parse doc comment.', $th->getMessage());
        }
    }

    public function testParseInvalidDocComment(): void {
        try {
            PhpStanUtils::parseDocComment('invalid');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unexpected token "invalid", expected \'/**\' at offset 0 on line 1',
                $th->getMessage(),
            );
        }
    }
}
