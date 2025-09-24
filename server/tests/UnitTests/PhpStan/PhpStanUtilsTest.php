<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\ApiObjectInterface;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\FakeClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\FakeAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\FakeAnotherAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\NamespaceAA\FakeAAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceB\FakeBClass;

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
            $utils->resolveApiObjectClass(FakeApiObject::class)?->getName(),
        );
    }

    public function testResolveFullyQualified(): void {
        $utils = new PhpStanUtils();
        $class_name = FakeApiObject::class;
        $this->assertSame(
            FakeApiObject::class,
            $utils->resolveApiObjectClass("\\{$class_name}")?->getName(),
        );
    }

    public function testResolveInvalid(): void {
        $utils = new PhpStanUtils();
        $this->assertNull($utils->resolveApiObjectClass('Invalid'));
    }

    public function testResolveNonApiObject(): void {
        $utils = new PhpStanUtils();
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
        $class_name = FakePhpStanUtilsTypedEndpoint::class;
        $comment = <<<ZZZZZZZZZZ
            /**
             * @phpstan-import-type AliasedInt from {$class_name}
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

    public function testParseValidDocCommentWithScope(): void {
        $utils = new PhpStanUtils();
        $comment = (new \ReflectionClass(FakeAClass::class))->getDocComment();

        $phpDocNode = $utils->parseDocComment($comment, __DIR__.'/Fake/NamespaceA/FakeAClass.php');
        $fake_another_class = FakeAnotherAClass::class;
        $fake_aa_class = FakeAAClass::class;
        $fake_b_class = FakeBClass::class;
        $fake_class = FakeClass::class;
        $this->assertSame(
            "{$fake_another_class}<{$fake_aa_class}<{$fake_b_class}<{$fake_class}<string>>>>",
            "{$phpDocNode?->getExtendsTagValues()[0]->type}",
        );
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

    public function testGetFileScope(): void {
        $utils = new PhpStanUtils();
        $this->assertSame([
            'PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA',
            [
                'FakeTopLevelClass' => 'PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\FakeClass',
                'FakeAAClass' => 'PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\NamespaceAA\FakeAAClass',
                'FakeBClass' => 'PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceB\FakeBClass',
            ],
        ], $utils->getFileScopeInfo(__DIR__.'/Fake/NamespaceA/FakeAClass.php'));
        $this->assertSame([null, []], $utils->getFileScopeInfo(__DIR__.'/Fake/InexistentClass.php'));
        $this->assertSame([null, []], $utils->getFileScopeInfo(null));
    }
}
