<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PhpTypeScriptApi\PhpStan\ApiObjectInterface;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\FakeMyArray;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\FakeTopClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\FakeAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\FakeAnotherAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\FakeSameFileAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\NamespaceAA\FakeAAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceB\FakeBClass;

/**
 * @phpstan-import-type AliasCache from PhpStanUtils
 *
 * @internal
 *
 * @coversNothing
 */
class PhpStanUtilsForTest extends PhpStanUtils {
    /** @return array<string, true> */
    public function testOnlyGetAliasCacheKeys(): array {
        $keys = [];
        foreach (array_keys($this->alias_cache) as $key) {
            $keys[$key] = true;
        }
        return $keys;
    }
}

/**
 * @phpstan-import-type InfiniteLoop from FakeLoop2
 */
class FakeLoop1 {
}

/**
 * @phpstan-import-type InfiniteLoop from FakeLoop1
 */
class FakeLoop2 {
}

/**
 * @phpstan-type AliasedUtil1String non-empty-string
 *
 * @phpstan-import-type AliasedUtil2Int from FakeUtil2 as AliasedUtil1Int
 * @phpstan-import-type AliasedUtil2IntArray from FakeUtil2
 *
 * @phpstan-type AliasedUtil1IntArray array<AliasedUtil1Int>
 * @phpstan-type AliasedUtil1Util3Minimal FakeUtil3<AliasedUtil1Int, AliasedUtil1String>
 */
class FakeUtil1 {
}

/**
 * @phpstan-type AliasedUtil2Int int<2, max>
 * @phpstan-type AliasedUtil2IntArray array<AliasedUtil2Int>
 */
class FakeUtil2 {
}

/**
 * @template T
 * @template U of string
 * @template V = int
 * @template W of int = int<0, max>
 *
 * @phpstan-type AliasedUtil3Item array{t: T, u: U, v: V, w: W}
 */
class FakeUtil3 {
}

/**
 * @phpstan-type AliasedInt int
 *
 * @phpstan-import-type AliasedUtil1String from FakeUtil1
 *
 * @phpstan-type AliasedUtil1StringArray array<AliasedUtil1String>
 *
 * @phpstan-import-type AliasedUtil1IntArray from FakeUtil1
 *
 * @phpstan-type AliasedUtil1IntArrayArray array<AliasedUtil1IntArray>
 */
class FakePhpStanUtilsTypedEndpoint {
}

/**
 * @implements ApiObjectInterface<'foo'>
 */
class FakeApiObject implements ApiObjectInterface {
    public function toWire(): mixed {
        return 'foo';
    }

    public static function fromWire(mixed $data): FakeApiObject {
        if ($data !== 'foo') {
            throw new \InvalidArgumentException("FakeApiObject must be foo");
        }
        return new FakeApiObject();
    }
}

/**
 * @phpstan-import-type AliasedUtil1StringArray from FakePhpStanUtilsTypedEndpoint
 *
 * @implements ApiObjectInterface<AliasedUtil1StringArray>
 */
class FakeAliasApiObject implements ApiObjectInterface {
    public function toWire(): mixed {
        return ['test'];
    }

    public static function fromWire(mixed $data): FakeAliasApiObject {
        if (!is_array($data)) {
            throw new \InvalidArgumentException("FakeAliasApiObject must be array");
        }
        for ($idx1 = 0; $idx1 < count($data); $idx1++) {
            if (!is_string($data[$idx1])) {
                throw new \InvalidArgumentException("FakeAliasApiObject[{$idx1}] must be string");
            }
        }
        return new FakeAliasApiObject();
    }
}

/**
 * @phpstan-import-type AliasedUtil1StringArray from FakePhpStanUtilsTypedEndpoint
 *
 * @implements ApiObjectInterface<FakeMyArray<int>>
 */
class FakeGenericApiObject implements ApiObjectInterface {
    public function toWire(): mixed {
        return new FakeMyArray([1]);
    }

    public static function fromWire(mixed $data): FakeGenericApiObject {
        if (!is_array($data)) {
            throw new \InvalidArgumentException("FakeGenericApiObject must be array");
        }
        for ($idx1 = 0; $idx1 < count($data); $idx1++) {
            if (!is_int($data[$idx1])) {
                throw new \InvalidArgumentException("FakeGenericApiObject[{$idx1}] must be int");
            }
        }
        return new FakeGenericApiObject();
    }
}

/**
 * @template T
 */
interface BogusInterface {
}

/**
 * @template T
 */
interface TheInterface {
    /** @return T */
    public function get(): mixed;
}

class NonGenericSuperClass {
}

/**
 * @template T
 * @template U
 */
class SuperClass extends NonGenericSuperClass {
}

/**
 * @template T
 *
 * @phpstan-import-type AliasedUtil1String from FakeUtil1
 *
 * @phpstan-type AliasedString string
 * @phpstan-type AliasedArray array<AliasedUtil1String>
 *
 * @extends SuperClass<T, array{20: AliasedUtil1String, 21: AliasedString, 22: AliasedArray, 23: T}>
 *
 * @implements BogusInterface<array{30: AliasedUtil1String, 31: AliasedString, 32: AliasedArray, 33: T}>
 * @implements TheInterface<array{10: AliasedUtil1String, 11: AliasedString, 12: AliasedArray, 13: T}>
 */
class IntermediateClass extends SuperClass implements BogusInterface, TheInterface {
    /** @param array<T> $value */
    protected function __construct(
        protected mixed $value,
    ) {
    }

    /** @return array<array<T>> */
    public function get(): mixed {
        return $this->value;
    }
}

/**
 * @phpstan-import-type AliasedUtil1String from FakeUtil1
 *
 * @phpstan-type AliasedString string
 * @phpstan-type AliasedArray array<AliasedUtil1String>
 *
 * @extends IntermediateClass<array{0: AliasedUtil1String, 1: AliasedString, 2: AliasedArray}>
 */
class SubClass extends IntermediateClass {
    public function __construct() {
        parent::__construct([['a', '', ['a']]]);
    }
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\PhpStanUtils
 */
final class PhpStanUtilsTest extends UnitTestCase {
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

    public function testAliasApiObjectTypeNodeFull(): void {
        $utils = new PhpStanUtils();
        $node = $utils->getApiObjectTypeNode(FakeAliasApiObject::class);
        $this->assertSame("array<non-empty-string>", "{$node}");
    }

    public function testAliasApiObjectTypeNodeFullyQualified(): void {
        $utils = new PhpStanUtils();
        $class_name = FakeAliasApiObject::class;
        $node = $utils->getApiObjectTypeNode("\\{$class_name}");
        $this->assertSame("array<non-empty-string>", "{$node}");
    }

    public function testGenericApiObjectTypeNodeFull(): void {
        $utils = new PhpStanUtils();
        $expected_name = FakeMyArray::class;
        $node = $utils->getApiObjectTypeNode(FakeGenericApiObject::class);
        $this->assertSame("{$expected_name}<int>", "{$node}");
    }

    public function testGenericApiObjectTypeNodeFullyQualified(): void {
        $utils = new PhpStanUtils();
        $class_name = FakeGenericApiObject::class;
        $expected_name = FakeMyArray::class;
        $node = $utils->getApiObjectTypeNode("\\{$class_name}");
        $this->assertSame("{$expected_name}<int>", "{$node}");
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

        $fake_util_3_class = FakeUtil3::class;
        $this->assertEquals([
            'AliasedUtil1String' => ['type' => $this->getTypeNode('non-empty-string')],
            'AliasedUtil1IntArray' => ['type' => $this->getTypeNode('array<AliasedUtil1Int>')],
            'AliasedUtil1Util3Minimal' => ['type' => $this->getTypeNode("{$fake_util_3_class}<AliasedUtil1Int, AliasedUtil1String>")],
            'AliasedUtil1Int' => ['namespace' => FakeUtil2::class, 'name' => 'AliasedUtil2Int'],
            'AliasedUtil2IntArray' => ['namespace' => FakeUtil2::class, 'name' => 'AliasedUtil2IntArray'],
        ], $utils->getAliases(FakeUtil1::class));
        $this->assertEquals([
            'AliasedUtil3Item' => ['type' => $this->getTypeNode('array{t: T, u: U, v: V, w: W}')],
        ], $utils->getAliases(FakeUtil3::class));
        try {
            $utils->getAliases(FakeUtil3::class, []);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame("Expected 2-4 generic arguments, but got '<>'", $th->getMessage());
        }
        $this->assertEquals([
            'T' => ['type' => $this->getTypeNode('int<min, 0>')],
            'U' => ['type' => $this->getTypeNode('numeric-string')],
            'V' => ['type' => $this->getTypeNode('int')],
            'W' => ['type' => $this->getTypeNode('int<0, max>')],
            'AliasedUtil3Item' => ['type' => $this->getTypeNode('array{t: T, u: U, v: V, w: W}')],
        ], $utils->getAliases(FakeUtil3::class, [
            $this->getTypeNode('int<min, 0>'),
            $this->getTypeNode('numeric-string'),
        ]));
    }

    public function testResolveType(): void {
        $utils = new PhpStanUtilsForTest();
        $this->assertEquals(
            $this->getTypeNode("non-empty-string"),
            $utils->resolveType($this->getTypeNode("non-empty-string"), FakeUtil1::class),
        );

        $this->assertEquals(
            $this->getTypeNode("non-empty-string"),
            $utils->resolveType($this->getTypeNode("AliasedUtil1String"), FakeUtil1::class),
        );
        $this->assertEquals(
            $this->getTypeNode("int<2, max>"),
            $utils->resolveType($this->getTypeNode("AliasedUtil1Int"), FakeUtil1::class),
        );
        $this->assertEquals(
            $this->getTypeNode("array<int<2, max>>"),
            $utils->resolveType($this->getTypeNode("AliasedUtil2IntArray"), FakeUtil1::class),
        );
        $fake_util_3_class = FakeUtil3::class;
        $this->assertEquals(
            $this->getTypeNode("{$fake_util_3_class}<int<2, max>, non-empty-string>"),
            $utils->resolveType($this->getTypeNode("AliasedUtil1Util3Minimal"), FakeUtil1::class),
        );
        $this->assertEquals(
            $this->getTypeNode("array{t: T, u: U, v: V, w: W}"),
            $utils->resolveType($this->getTypeNode("AliasedUtil3Item"), FakeUtil3::class, null),
        );
        try {
            $utils->resolveType($this->getTypeNode("AliasedUtil3Item"), FakeUtil3::class, []);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame("Expected 2-4 generic arguments, but got '<>'", $th->getMessage());
        }
        $this->assertEquals(
            $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    t: int<0, 1>,
                    u: class-string,
                    v: int,
                    w: int<0, max>,
                }
                ZZZZZZZZZZ),
            $utils->resolveType($this->getTypeNode("AliasedUtil3Item"), FakeUtil3::class, [
                $this->getTypeNode("int<0, 1>"),
                $this->getTypeNode("class-string"),
            ]),
        );

        $this->assertEquals([
            FakeUtil1::class => true,
            FakeUtil2::class => true,
            FakeUtil3::class => true,
        ], $utils->testOnlyGetAliasCacheKeys());
    }

    public function testResolveTypeInfiniteLoop(): void {
        $utils = new PhpStanUtilsForTest();
        try {
            $utils->resolveType($this->getTypeNode('InfiniteLoop'), FakeLoop1::class);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertEquals([
                FakeLoop1::class => true,
                FakeLoop2::class => true,
            ], $utils->testOnlyGetAliasCacheKeys());
            $this->assertSame(
                'Maximum recusion level (100) reached: Failed importing InfiniteLoop from PhpTypeScriptApi\Tests\UnitTests\PhpStan\FakeLoop1',
                $th->getMessage(),
            );
        }
    }

    public function testRewriteType(): void {
        $utils = new PhpStanUtilsForTest();
        $this->assertEquals(
            [$this->getTypeNode("non-empty-string"), []],
            $utils->rewriteType($this->getTypeNode("non-empty-string"), FakeUtil1::class),
        );
        $this->assertEquals(
            [$this->getTypeNode("PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String"), [
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String' => $this->getTypeNode('non-empty-string'),
            ]],
            $utils->rewriteType($this->getTypeNode("AliasedUtil1String"), FakeUtil1::class),
        );
        $this->assertEquals(
            [$this->getTypeNode("PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1Int"), [
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1Int' => $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2Int'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2Int' => $this->getTypeNode('int<2, max>'),
            ]],
            $utils->rewriteType($this->getTypeNode("AliasedUtil1Int"), FakeUtil1::class),
        );
        $this->assertEquals(
            [$this->getTypeNode("PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil2IntArray"), [
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil2IntArray' => $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2IntArray'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2IntArray' => $this->getTypeNode('array<PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2Int>'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2Int' => $this->getTypeNode('int<2, max>'),
            ]],
            $utils->rewriteType($this->getTypeNode("AliasedUtil2IntArray"), FakeUtil1::class),
        );
        $fake_util_3_class = FakeUtil3::class;
        $this->assertEquals(
            [$this->getTypeNode("PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1Util3Minimal"), [
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1Util3Minimal' => $this->getTypeNode(<<<ZZZZZZZZZZ
                    {$fake_util_3_class}<
                        PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1Int,
                        PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String,
                    >
                    ZZZZZZZZZZ),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1Int' => $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2Int'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String' => $this->getTypeNode('non-empty-string'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil2_AliasedUtil2Int' => $this->getTypeNode('int<2, max>'),
            ]],
            $utils->rewriteType($this->getTypeNode("AliasedUtil1Util3Minimal"), FakeUtil1::class),
        );
        $this->assertEquals(
            [$this->getTypeNode("PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_AliasedUtil3Item"), [
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_AliasedUtil3Item' => $this->getTypeNode('array{t: T, u: U, v: V, w: W}'),
            ]],
            $utils->rewriteType($this->getTypeNode("AliasedUtil3Item"), FakeUtil3::class, null),
        );
        try {
            $utils->rewriteType($this->getTypeNode("AliasedUtil3Item"), FakeUtil3::class, []);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame("Expected 2-4 generic arguments, but got '<>'", $th->getMessage());
        }
        $this->assertEquals(
            [$this->getTypeNode("PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_AliasedUtil3Item"), [
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_AliasedUtil3Item' => $this->getTypeNode(<<<'ZZZZZZZZZZ'
                    array{
                        t: PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_T,
                        u: PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_U,
                        v: PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_V,
                        w: PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_W,
                    }
                    ZZZZZZZZZZ),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_T' => $this->getTypeNode('int<0, 1>'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_U' => $this->getTypeNode('class-string'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_V' => $this->getTypeNode('int'),
                'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil3_W' => $this->getTypeNode('int<0, max>'),
            ]],
            $utils->rewriteType($this->getTypeNode("AliasedUtil3Item"), FakeUtil3::class, [
                $this->getTypeNode("int<0, 1>"),
                $this->getTypeNode("class-string"),
            ]),
        );
    }

    public function testResolveEndpointImportAlias(): void {
        $utils = new PhpStanUtilsForTest();
        $this->assertEquals(
            ['type' => $this->getTypeNode("int")],
            $utils->resolveImportAlias(['namespace' => FakePhpStanUtilsTypedEndpoint::class, 'name' => 'AliasedInt'])
        );
        $this->assertEquals(
            ['namespace' => FakeUtil1::class, 'name' => 'AliasedUtil1String'],
            $utils->resolveImportAlias(['namespace' => FakePhpStanUtilsTypedEndpoint::class, 'name' => 'AliasedUtil1String'])
        );
        $this->assertEquals(
            ['type' => $this->getTypeNode("array<AliasedUtil1String>")],
            $utils->resolveImportAlias(['namespace' => FakePhpStanUtilsTypedEndpoint::class, 'name' => 'AliasedUtil1StringArray'])
        );
        $this->assertEquals(
            ['namespace' => FakeUtil1::class, 'name' => 'AliasedUtil1IntArray'],
            $utils->resolveImportAlias(['namespace' => FakePhpStanUtilsTypedEndpoint::class, 'name' => 'AliasedUtil1IntArray'])
        );
        $this->assertEquals(
            ['type' => $this->getTypeNode("array<AliasedUtil1IntArray>")],
            $utils->resolveImportAlias(['namespace' => FakePhpStanUtilsTypedEndpoint::class, 'name' => 'AliasedUtil1IntArrayArray'])
        );
        $this->assertEquals([
            FakePhpStanUtilsTypedEndpoint::class => true,
        ], $utils->testOnlyGetAliasCacheKeys());
    }

    public function testResolveUtilImportAlias(): void {
        $utils = new PhpStanUtilsForTest();
        $this->assertEquals(
            ['type' => $this->getTypeNode("non-empty-string")],
            $utils->resolveImportAlias(['namespace' => FakeUtil1::class, 'name' => 'AliasedUtil1String'])
        );
        $this->assertEquals(
            ['namespace' => FakeUtil2::class, 'name' => 'AliasedUtil2Int'],
            $utils->resolveImportAlias(['namespace' => FakeUtil1::class, 'name' => 'AliasedUtil1Int'])
        );
        $this->assertEquals(
            ['type' => $this->getTypeNode("array<AliasedUtil1Int>")],
            $utils->resolveImportAlias(['namespace' => FakeUtil1::class, 'name' => 'AliasedUtil1IntArray'])
        );
        $this->assertEquals([
            FakeUtil1::class => true,
        ], $utils->testOnlyGetAliasCacheKeys());
    }

    public function testResolveFakeAliases(): void {
        $utils = new PhpStanUtilsForTest();
        $this->assertEquals(
            ['type' => $this->getTypeNode("array{anotherA: FakeAnotherAType, aa: FakeAAType, b: FakeBType, top: FakeTopType, sameFileA: FakeSameFileAType}")],
            $utils->resolveImportAlias(['namespace' => FakeAClass::class, 'name' => 'FakeType'])
        );
        $this->assertEquals(
            ['namespace' => FakeAnotherAClass::class, 'name' => 'FakeAnotherAType'],
            $utils->resolveImportAlias(['namespace' => FakeAClass::class, 'name' => 'FakeAnotherAType'])
        );
        $this->assertEquals(
            ['namespace' => FakeAAClass::class, 'name' => 'FakeAAType'],
            $utils->resolveImportAlias(['namespace' => FakeAClass::class, 'name' => 'FakeAAType'])
        );
        $this->assertEquals(
            ['namespace' => FakeBClass::class, 'name' => 'FakeBType'],
            $utils->resolveImportAlias(['namespace' => FakeAClass::class, 'name' => 'FakeBType'])
        );
        $this->assertEquals(
            ['namespace' => FakeTopClass::class, 'name' => 'FakeTopType'],
            $utils->resolveImportAlias(['namespace' => FakeAClass::class, 'name' => 'FakeTopType'])
        );
        $this->assertEquals(
            ['namespace' => FakeSameFileAClass::class, 'name' => 'FakeSameFileAType'],
            $utils->resolveImportAlias(['namespace' => FakeAClass::class, 'name' => 'FakeSameFileAType'])
        );
        $this->assertEquals([
            FakeAClass::class => true,
        ], $utils->testOnlyGetAliasCacheKeys());
    }

    public function testGetImportedAliasesError(): void {
        $utils = new PhpStanUtilsForTest();
        try {
            $utils->resolveImportAlias(['namespace' => 'Invalid', 'name' => 'AliasedInt']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertEquals([
                'Invalid' => true,
            ], $utils->testOnlyGetAliasCacheKeys());
            $this->assertSame(
                'Failed importing AliasedInt from Invalid',
                $th->getMessage(),
            );
        }
    }

    public function testParseClassDocComment(): void {
        $utils = new PhpStanUtils();

        $phpDocNode = $utils->parseClassDocComment(FakeUtil1::class);

        $this->assertSame('AliasedUtil1String', "{$phpDocNode?->getTypeAliasTagValues()[0]->alias}");
        $this->assertSame('non-empty-string', "{$phpDocNode?->getTypeAliasTagValues()[0]->type}");
        $this->assertSame('AliasedUtil1IntArray', "{$phpDocNode?->getTypeAliasTagValues()[1]->alias}");
        $this->assertSame('array<AliasedUtil1Int>', "{$phpDocNode?->getTypeAliasTagValues()[1]->type}");
    }

    public function testParseReflectionClassDocComment(): void {
        $utils = new PhpStanUtils();

        $phpDocNode = $utils->parseReflectionClassDocComment(new \ReflectionClass(FakeUtil1::class));

        $this->assertSame('AliasedUtil1String', "{$phpDocNode?->getTypeAliasTagValues()[0]->alias}");
        $this->assertSame('non-empty-string', "{$phpDocNode?->getTypeAliasTagValues()[0]->type}");
        $this->assertSame('AliasedUtil1IntArray', "{$phpDocNode?->getTypeAliasTagValues()[1]->alias}");
        $this->assertSame('array<AliasedUtil1Int>', "{$phpDocNode?->getTypeAliasTagValues()[1]->type}");
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
        $fake_class = FakeTopClass::class;
        $fake_same_file_a_class = FakeSameFileAClass::class;
        $this->assertSame(
            "{$fake_another_class}<FakeAnotherAType, {$fake_aa_class}<FakeAAType, {$fake_b_class}<FakeBType, {$fake_class}<FakeTopType, {$fake_same_file_a_class}<FakeSameFileAType, string>>>>>",
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
                'FakeTopLevelClass' => 'PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\FakeTopClass',
                'FakeAAClass' => 'PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\NamespaceAA\FakeAAClass',
                'FakeBClass' => 'PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceB\FakeBClass',
            ],
        ], $utils->getFileScopeInfo(__DIR__.'/Fake/NamespaceA/FakeAClass.php'));
        $this->assertSame([null, []], $utils->getFileScopeInfo(__DIR__.'/Fake/InexistentClass.php'));
        $this->assertSame([null, []], $utils->getFileScopeInfo(null));
    }

    public function testGetSuperInterfaceGenerics(): void {
        $utils = new PhpStanUtils();
        $this->assertEquals([
            $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    10: non-empty-string,
                    11: string,
                    12: array<non-empty-string>,
                    13: array{
                        0: non-empty-string,
                        1: string,
                        2: array<non-empty-string>,
                    },
                }
                ZZZZZZZZZZ),
        ], $utils->getSuperGenerics(SubClass::class, TheInterface::class));
    }

    public function testGetSuperInterfaceGenericsWithExports(): void {
        $utils = new PhpStanUtils();
        $exports = [];
        $fn = function (Node $node, string $class_name, array $generic_args) use ($utils, &$exports) {
            [$node, $new_exports] = $utils->rewriteType($node, $class_name, $generic_args);
            foreach ($new_exports as $key => $value) {
                $exports[$key] = $value;
            }
            return $node;
        };
        $this->assertEquals([
            $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    10: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedUtil1String,
                    11: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedString,
                    12: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedArray,
                    13: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_T,
                }
                ZZZZZZZZZZ),
        ], $utils->getSuperGenerics(SubClass::class, TheInterface::class, $fn));
        $this->assertEquals([
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String' => $this->getTypeNode('non-empty-string'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedUtil1String' => $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedString' => $this->getTypeNode('string'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedArray' => $this->getTypeNode('array<PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedUtil1String>'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedUtil1String' => $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedString' => $this->getTypeNode('string'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedArray' => $this->getTypeNode('array<PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedUtil1String>'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_T' => $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    0: PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedUtil1String,
                    1: PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedString,
                    2: PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedArray,
                }
                ZZZZZZZZZZ),
        ], $exports);
    }

    public function testGetSuperClassGenerics(): void {
        $utils = new PhpStanUtils();
        $this->assertEquals([
            $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    0: non-empty-string,
                    1: string,
                    2: array<non-empty-string>,
                }
                ZZZZZZZZZZ),
            $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    20: non-empty-string,
                    21: string,
                    22: array<non-empty-string>,
                    23: array{
                        0: non-empty-string,
                        1: string,
                        2: array<non-empty-string>,
                    },
                }
                ZZZZZZZZZZ),
        ], $utils->getSuperGenerics(SubClass::class, SuperClass::class));
    }

    public function testGetSuperClassGenericsWithExports(): void {
        $utils = new PhpStanUtils();
        $exports = [];
        $fn = function (Node $node, string $class_name, array $generic_args) use ($utils, &$exports) {
            [$node, $new_exports] = $utils->rewriteType($node, $class_name, $generic_args);
            foreach ($new_exports as $key => $value) {
                $exports[$key] = $value;
            }
            return $node;
        };
        $this->assertEquals([
            $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_T'),
            $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    20: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedUtil1String,
                    21: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedString,
                    22: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedArray,
                    23: PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_T,
                }
                ZZZZZZZZZZ),
        ], $utils->getSuperGenerics(SubClass::class, SuperClass::class, $fn));
        $this->assertEquals([
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String' => $this->getTypeNode('non-empty-string'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedUtil1String' => $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedString' => $this->getTypeNode('string'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedArray' => $this->getTypeNode('array<PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedUtil1String>'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedUtil1String' => $this->getTypeNode('PhpTypeScriptApi_Tests_UnitTests_PhpStan_FakeUtil1_AliasedUtil1String'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedString' => $this->getTypeNode('string'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedArray' => $this->getTypeNode('array<PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_AliasedUtil1String>'),
            'PhpTypeScriptApi_Tests_UnitTests_PhpStan_IntermediateClass_T' => $this->getTypeNode(<<<'ZZZZZZZZZZ'
                array{
                    0: PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedUtil1String,
                    1: PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedString,
                    2: PhpTypeScriptApi_Tests_UnitTests_PhpStan_SubClass_AliasedArray,
                }
                ZZZZZZZZZZ),
        ], $exports);
    }

    public function testGetSuperGenericsNotSuperClass(): void {
        $utils = new PhpStanUtils();
        $intermediate_class_name = IntermediateClass::class;
        $sub_class_name = SubClass::class;
        try {
            $utils->getSuperGenerics($intermediate_class_name, $sub_class_name);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "getSuperGenerics: {$intermediate_class_name} is not a subclass of {$sub_class_name}",
                $th->getMessage(),
            );
        }
    }

    public function testGetSuperGenericsNotSuperInterface(): void {
        $utils = new PhpStanUtils();
        $super_class_name = SuperClass::class;
        $the_interface_name = TheInterface::class;
        try {
            $utils->getSuperGenerics($super_class_name, $the_interface_name);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "getSuperGenerics: {$super_class_name} is not a subclass of {$the_interface_name}",
                $th->getMessage(),
            );
        }
    }

    public function testGetSuperGenericsNonGenericSuperClass(): void {
        $utils = new PhpStanUtils();
        $this->assertSame([], $utils->getSuperGenerics(SubClass::class, NonGenericSuperClass::class));
    }

    public function testGetTemplateAliases(): void {
        $utils = new PhpStanUtils();
        $phpDocNode = $utils->parseDocComment(<<<'ZZZZZZZZZZ'
            /**
             * @template T
             * @template U of string
             * @template V = int
             * @template W of int = int<0, max>
             * @return int
             */
            ZZZZZZZZZZ);
        $getGenericArgs = function (string $type_string) {
            $node = $this->getTypeNode($type_string);
            if (!$node instanceof GenericTypeNode) {
                throw new \Exception("Not generic: {$type_string}");
            }
            return $node->genericTypes;
        };

        $this->assertEquals([
            'T' => ['type' => $this->getTypeNode("string")],
            'U' => ['type' => $this->getTypeNode("'foo'")],
            'V' => ['type' => $this->getTypeNode("int")],
            'W' => ['type' => $this->getTypeNode("int<0, max>")],
        ], $utils->getTemplateAliases($phpDocNode, $getGenericArgs("A<string, 'foo'>")));
        $this->assertEquals([
            'T' => ['type' => $this->getTypeNode("string")],
            'U' => ['type' => $this->getTypeNode("'foo'")],
            'V' => ['type' => $this->getTypeNode("3")],
            'W' => ['type' => $this->getTypeNode("-1")],
        ], $utils->getTemplateAliases($phpDocNode, $getGenericArgs("A<string, 'foo', 3, -1>")));
        // We don't do checks
        $this->assertEquals([
            'T' => ['type' => $this->getTypeNode("string")],
            'U' => ['type' => $this->getTypeNode("3")],
            'V' => ['type' => $this->getTypeNode("'foo'")],
            'W' => ['type' => $this->getTypeNode("'bar'")],
        ], $utils->getTemplateAliases($phpDocNode, $getGenericArgs("A<string, 3, 'foo', 'bar'>")));
        try {
            $utils->getTemplateAliases($phpDocNode, $getGenericArgs("A<'too few'>"));
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 2-4 generic arguments, but got '<'too few'>'",
                $th->getMessage(),
            );
        }
        try {
            $utils->getTemplateAliases($phpDocNode, $getGenericArgs("A<'too', 'many', 'args', 'in', 'here'>"));
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 2-4 generic arguments, but got '<'too', 'many', 'args', 'in', 'here'>'",
                $th->getMessage(),
            );
        }

        // Null-related edge cases
        $this->assertEquals([], $utils->getTemplateAliases(null, $getGenericArgs("A<string, 'foo'>")));
    }

    public function testGetPrettyAlias(): void {
        $utils = new PhpStanUtils();
        $this->assertSame("array<string>", $utils->getPrettyAlias([
            'type' => $this->getTypeNode('array<string>'),
        ]));
        $this->assertSame("Namespace::Name", $utils->getPrettyAlias([
            'namespace' => 'Namespace',
            'name' => 'Name',
        ]));
    }

    public function testGetPrettyAliasCache(): void {
        $utils = new PhpStanUtils();

        $this->assertSame(<<<'ZZZZZZZZZZ'
            ---
            Class1
                StringArrayType => array<string>
                Imported => Namespace::Name

            Class2
                IntArrayType => array<int>
            ---
            
            ZZZZZZZZZZ, $utils->getPrettyAliasCache([
            'Class1' => [
                'StringArrayType' => [
                    'type' => $this->getTypeNode('array<string>'),
                ],
                'Imported' => [
                    'namespace' => 'Namespace',
                    'name' => 'Name',
                ],
            ],
            'Class2' => [
                'IntArrayType' => [
                    'type' => $this->getTypeNode('array<int>'),
                ],
            ],
        ]));
    }
}
