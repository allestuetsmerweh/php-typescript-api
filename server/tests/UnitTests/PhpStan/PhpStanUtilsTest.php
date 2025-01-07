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
    public function testResolveFull(): void {
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveClass(FakeApiObject::class, [])?->getName(),
        );
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveApiObjectClass(FakeApiObject::class)?->getName(),
        );
    }

    public function testResolveFullyQualified(): void {
        $class_name = FakeApiObject::class;
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveClass("\\{$class_name}", [])?->getName(),
        );
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveApiObjectClass("\\{$class_name}")?->getName(),
        );
    }

    public function testResolveShort(): void {
        $registry = ['FakeApiObject' => FakeApiObject::class];
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveClass('FakeApiObject', $registry)?->getName(),
        );
        PhpStanUtils::registerApiObject(FakeApiObject::class);
        $this->assertSame(
            FakeApiObject::class,
            PhpStanUtils::resolveApiObjectClass('FakeApiObject')?->getName(),
        );
    }

    public function testResolveInvalid(): void {
        $this->assertNull(PhpStanUtils::resolveClass('Invalid', []));
        $this->assertNull(PhpStanUtils::resolveApiObjectClass('Invalid'));
    }

    public function testResolveNonApiObject(): void {
        $this->assertSame(
            PhpStanUtilsTest::class,
            PhpStanUtils::resolveClass(PhpStanUtilsTest::class, [])?->getName(),
        );
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

    public function testGetAliases(): void {
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @phpstan-type AliasedInt int
             * @phpstan-type AliasedArray array<string, int>
             * @phpstan-type AliasedRecursive array{recursive: AliasedRecursive|int}
             */
            ZZZZZZZZZZ;
        $phpDocNode = PhpStanUtils::parseDocComment($comment);

        $this->assertEquals([
            'AliasedInt' => $this->getTypeNode("int"),
            'AliasedArray' => $this->getTypeNode("array<string, int>"),
            'AliasedRecursive' => $this->getTypeNode("array{recursive: AliasedRecursive|int}"),
        ], PhpStanUtils::getAliases($phpDocNode));
    }

    public function testGetImportedAliases(): void {
        PhpStanUtils::registerTypeImport(FakePhpStanUtilsTypedEndpoint::class);
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @phpstan-import-type AliasedInt from FakePhpStanUtilsTypedEndpoint
             */
            ZZZZZZZZZZ;
        $phpDocNode = PhpStanUtils::parseDocComment($comment);

        $this->assertEquals([
            'AliasedInt' => $this->getTypeNode("int"),
        ], PhpStanUtils::getAliases($phpDocNode));
    }

    public function testGetImportedAliasesError(): void {
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @phpstan-import-type AliasedInt from Invalid
             */
            ZZZZZZZZZZ;
        $phpDocNode = PhpStanUtils::parseDocComment($comment);
        try {
            PhpStanUtils::getAliases($phpDocNode);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Failed importing AliasedInt from Invalid',
                $th->getMessage(),
            );
        }
    }

    public function testParseValidDocComment(): void {
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @param string $arg0
             * @return int
             */
            ZZZZZZZZZZ;

        $phpDocNode = PhpStanUtils::parseDocComment($comment);

        $this->assertSame('string', "{$phpDocNode?->getParamTagValues()[0]->type}");
        $this->assertSame('int', "{$phpDocNode?->getReturnTagValues()[0]->type}");
    }

    public function testParseEmptyDocComment(): void {
        $phpDocNode = PhpStanUtils::parseDocComment('/** Empty */');

        $this->assertSame([], $phpDocNode?->getParamTagValues());
        $this->assertSame([], $phpDocNode->getReturnTagValues());
    }

    public function testParseInexistentDocComment(): void {
        $this->assertNull(PhpStanUtils::parseDocComment(false));
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
