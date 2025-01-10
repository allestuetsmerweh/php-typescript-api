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
        $utils = new PhpStanUtils();
        $this->assertSame(
            FakeApiObject::class,
            $utils->resolveClass(FakeApiObject::class, [])?->getName(),
        );
        $this->assertSame(
            FakeApiObject::class,
            $utils->resolveApiObjectClass(FakeApiObject::class)?->getName(),
        );
    }

    public function testResolveFullyQualified(): void {
        $utils = new PhpStanUtils();
        $class_name = FakeApiObject::class;
        $this->assertSame(
            FakeApiObject::class,
            $utils->resolveClass("\\{$class_name}", [])?->getName(),
        );
        $this->assertSame(
            FakeApiObject::class,
            $utils->resolveApiObjectClass("\\{$class_name}")?->getName(),
        );
    }

    public function testResolveShort(): void {
        $utils = new PhpStanUtils();
        $registry = ['FakeApiObject' => FakeApiObject::class];
        $this->assertSame(
            FakeApiObject::class,
            $utils->resolveClass('FakeApiObject', $registry)?->getName(),
        );
        $utils->registerApiObject(FakeApiObject::class);
        $this->assertSame(
            FakeApiObject::class,
            $utils->resolveApiObjectClass('FakeApiObject')?->getName(),
        );
    }

    public function testResolveInvalid(): void {
        $utils = new PhpStanUtils();
        $this->assertNull($utils->resolveClass('Invalid', []));
        $this->assertNull($utils->resolveApiObjectClass('Invalid'));
    }

    public function testResolveNonApiObject(): void {
        $utils = new PhpStanUtils();
        $this->assertSame(
            PhpStanUtilsTest::class,
            $utils->resolveClass(PhpStanUtilsTest::class, [])?->getName(),
        );
        $this->assertNull($utils->resolveApiObjectClass(PhpStanUtilsTest::class));
    }

    public function testApiObjectTypeNodeFull(): void {
        $utils = new PhpStanUtils();
        $node = $utils->getApiObjectTypeNode(FakeApiObject::class);
        $this->assertSame("'foo'", "{$node}");
    }

    public function testApiObjectTypeNodeFullyQualified(): void {
        $utils = new PhpStanUtils();
        $class_name = FakeApiObject::class;
        $node = $utils->getApiObjectTypeNode("\\{$class_name}");
        $this->assertSame("'foo'", "{$node}");
    }

    public function testApiObjectTypeNodeShort(): void {
        $utils = new PhpStanUtils();
        $utils->registerApiObject(FakeApiObject::class);

        $node = $utils->getApiObjectTypeNode('FakeApiObject');
        $this->assertSame("'foo'", "{$node}");
    }

    public function testApiObjectTypeNodeInvalid(): void {
        $utils = new PhpStanUtils();
        $this->assertNull($utils->getApiObjectTypeNode('Invalid'));
    }

    public function testApiObjectTypeNodeNonApiObject(): void {
        $utils = new PhpStanUtils();
        $this->assertNull($utils->getApiObjectTypeNode(PhpStanUtilsTest::class));
    }

    public function testGetAliases(): void {
        $utils = new PhpStanUtils();
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @phpstan-type AliasedInt int
             * @phpstan-type AliasedArray array<string, int>
             * @phpstan-type AliasedRecursive array{recursive: AliasedRecursive|int}
             */
            ZZZZZZZZZZ;
        $phpDocNode = $utils->parseDocComment($comment);

        $this->assertEquals([
            'AliasedInt' => $this->getTypeNode("int"),
            'AliasedArray' => $this->getTypeNode("array<string, int>"),
            'AliasedRecursive' => $this->getTypeNode("array{recursive: AliasedRecursive|int}"),
        ], $utils->getAliases($phpDocNode));
    }

    public function testGetImportedAliases(): void {
        $utils = new PhpStanUtils();
        $utils->registerTypeImport(FakePhpStanUtilsTypedEndpoint::class);
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @phpstan-import-type AliasedInt from FakePhpStanUtilsTypedEndpoint
             */
            ZZZZZZZZZZ;
        $phpDocNode = $utils->parseDocComment($comment);

        $this->assertEquals([
            'AliasedInt' => $this->getTypeNode("int"),
        ], $utils->getAliases($phpDocNode));
    }

    public function testGetImportedAliasesError(): void {
        $utils = new PhpStanUtils();
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @phpstan-import-type AliasedInt from Invalid
             */
            ZZZZZZZZZZ;
        $phpDocNode = $utils->parseDocComment($comment);
        try {
            $utils->getAliases($phpDocNode);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Failed importing AliasedInt from Invalid',
                $th->getMessage(),
            );
        }
    }

    public function testParseValidDocComment(): void {
        $utils = new PhpStanUtils();
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @param string $arg0
             * @return int
             */
            ZZZZZZZZZZ;

        $phpDocNode = $utils->parseDocComment($comment);

        $this->assertSame('string', "{$phpDocNode?->getParamTagValues()[0]->type}");
        $this->assertSame('int', "{$phpDocNode?->getReturnTagValues()[0]->type}");
    }

    public function testParseEmptyDocComment(): void {
        $utils = new PhpStanUtils();
        $phpDocNode = $utils->parseDocComment('/** Empty */');

        $this->assertSame([], $phpDocNode?->getParamTagValues());
        $this->assertSame([], $phpDocNode->getReturnTagValues());
    }

    public function testParseInexistentDocComment(): void {
        $utils = new PhpStanUtils();
        $this->assertNull($utils->parseDocComment(false));
    }

    public function testParseInvalidDocComment(): void {
        $utils = new PhpStanUtils();
        try {
            $utils->parseDocComment('invalid');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unexpected token "invalid", expected \'/**\' at offset 0 on line 1',
                $th->getMessage(),
            );
        }
    }
}
