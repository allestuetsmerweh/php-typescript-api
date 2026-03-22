<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

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
    /** @return AliasCache */
    public function testOnlyGetAliasCache(): array {
        return $this->alias_cache;
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
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @phpstan-type AliasedInt int
             * @phpstan-type AliasedArray array<string, int>
             * @phpstan-type AliasedRecursive array{recursive: AliasedRecursive|int}
             */
            ZZZZZZZZZZ;
        $phpDocNode = $utils->parseDocComment($comment);

        $this->assertEquals([
            'AliasedInt' => ['type' => $this->getTypeNode("int")],
            'AliasedArray' => ['type' => $this->getTypeNode("array<string, int>")],
            'AliasedRecursive' => ['type' => $this->getTypeNode("array{recursive: AliasedRecursive|int}")],
        ], $utils->getAliases($phpDocNode));
    }

    public function testGetFakeAliases(): void {
        $utils = new PhpStanUtils();
        $class_info = new \ReflectionClass(FakeAClass::class);
        $phpDocNode = $utils->parseDocComment(
            $class_info->getDocComment(),
            $class_info->getFileName() ?: null,
        );
        $this->assertEquals([
            'FakeType' => ['type' => $this->getTypeNode("array{anotherA: FakeAnotherAType, aa: FakeAAType,b: FakeBType,top: FakeTopType,sameFileA: FakeSameFileAType}")],
            'FakeAnotherAType' => ['namespace' => FakeAnotherAClass::class, 'name' => 'FakeAnotherAType'],
            'FakeAAType' => ['namespace' => FakeAAClass::class, 'name' => 'FakeAAType'],
            'FakeBType' => ['namespace' => FakeBClass::class, 'name' => 'FakeBType'],
            'FakeTopType' => ['namespace' => FakeTopClass::class, 'name' => 'FakeTopType'],
            'FakeSameFileAType' => ['namespace' => FakeSameFileAClass::class, 'name' => 'FakeSameFileAType'],
        ], $utils->getAliases($phpDocNode));
    }

    public function testGetImportedAliases(): void {
        $utils = new PhpStanUtils();
        $class_name = FakePhpStanUtilsTypedEndpoint::class;
        $comment = <<<ZZZZZZZZZZ
            /**
             * @phpstan-import-type AliasedInt from {$class_name}
             * @phpstan-import-type AliasedInt from {$class_name} as MyInt
             * @phpstan-import-type AliasedUtil1String from {$class_name}
             * @phpstan-import-type AliasedUtil1String from {$class_name} as MyString
             * @phpstan-import-type AliasedUtil1StringArray from {$class_name}
             * @phpstan-import-type AliasedUtil1StringArray from {$class_name} as MyStringArray
             * @phpstan-import-type AliasedUtil1IntArray from {$class_name}
             * @phpstan-import-type AliasedUtil1IntArray from {$class_name} as MyIntArray
             * @phpstan-import-type AliasedUtil1IntArrayArray from {$class_name}
             * @phpstan-import-type AliasedUtil1IntArrayArray from {$class_name} as MyIntArrayArray
             */
            ZZZZZZZZZZ;
        $phpDocNode = $utils->parseDocComment($comment);

        $this->assertEquals([
            'AliasedInt' => ['namespace' => $class_name, 'name' => 'AliasedInt'],
            'MyInt' => ['namespace' => $class_name, 'name' => 'AliasedInt'],
            'AliasedUtil1String' => ['namespace' => $class_name, 'name' => 'AliasedUtil1String'],
            'MyString' => ['namespace' => $class_name, 'name' => 'AliasedUtil1String'],
            'AliasedUtil1StringArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1StringArray'],
            'MyStringArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1StringArray'],
            'AliasedUtil1IntArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1IntArray'],
            'MyIntArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1IntArray'],
            'AliasedUtil1IntArrayArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1IntArrayArray'],
            'MyIntArrayArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1IntArrayArray'],
        ], $utils->getAliases($phpDocNode));
    }

    public function testGetImportedUtilAliases(): void {
        $utils = new PhpStanUtils();
        $class_name = FakeUtil1::class;
        $comment = <<<ZZZZZZZZZZ
            /**
             * @phpstan-import-type AliasedUtil1String from {$class_name}
             * @phpstan-import-type AliasedUtil1String from {$class_name} as MyString
             * @phpstan-import-type AliasedUtil1Int from {$class_name}
             * @phpstan-import-type AliasedUtil1Int from {$class_name} as MyInt
             * @phpstan-import-type AliasedUtil1IntArray from {$class_name}
             * @phpstan-import-type AliasedUtil1IntArray from {$class_name} as MyIntArray
             */
            ZZZZZZZZZZ;
        $phpDocNode = $utils->parseDocComment($comment);

        $this->assertEquals([
            'AliasedUtil1String' => ['namespace' => $class_name, 'name' => 'AliasedUtil1String'],
            'MyString' => ['namespace' => $class_name, 'name' => 'AliasedUtil1String'],
            'AliasedUtil1Int' => ['namespace' => $class_name, 'name' => 'AliasedUtil1Int'],
            'MyInt' => ['namespace' => $class_name, 'name' => 'AliasedUtil1Int'],
            'AliasedUtil1IntArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1IntArray'],
            'MyIntArray' => ['namespace' => $class_name, 'name' => 'AliasedUtil1IntArray'],
        ], $utils->getAliases($phpDocNode));
    }

    public function testResolveType(): void {
        $utils = new PhpStanUtilsForTest();
        $this->assertEquals(
            $this->getTypeNode("non-empty-string"),
            $utils->resolveType($this->getTypeNode("non-empty-string"), []),
        );
        $this->assertEquals(
            $this->getTypeNode("int"),
            $utils->resolveType($this->getTypeNode("FakeIntAlias"), [
                'FakeIntAlias' => ['type' => $this->getTypeNode("int")],
            ]),
        );
        $this->assertEquals(
            $this->getTypeNode("int"),
            $utils->resolveType($this->getTypeNode("FakeIntAliasAlias"), [
                'FakeIntAliasAlias' => ['type' => $this->getTypeNode("FakeIntAlias")],
                'FakeIntAlias' => ['type' => $this->getTypeNode("int")],
            ]),
        );
        $this->assertEquals(
            $this->getTypeNode("non-empty-string"),
            $utils->resolveType($this->getTypeNode("FakeImportAlias"), [
                'FakeImportAlias' => [
                    'namespace' => FakeUtil1::class,
                    'name' => 'AliasedUtil1String',
                ],
            ]),
        );
        $this->assertEquals(
            $this->getTypeNode("int<2, max>"),
            $utils->resolveType($this->getTypeNode("FakeImportAlias"), [
                'FakeImportAlias' => [
                    'namespace' => FakeUtil1::class,
                    'name' => 'AliasedUtil1Int',
                ],
            ]),
        );
        $this->assertEquals(
            $this->getTypeNode("array<int<2, max>>"),
            $utils->resolveType($this->getTypeNode("FakeImportAlias"), [
                'FakeImportAlias' => [
                    'namespace' => FakeUtil1::class,
                    'name' => 'AliasedUtil2IntArray',
                ],
            ]),
        );
    }

    public function testResolveTypeAlias(): void {
        $utils = new PhpStanUtilsForTest();
        $this->assertEquals(
            $this->getTypeNode("non-empty-string"),
            $utils->resolveAlias(['type' => $this->getTypeNode("non-empty-string")])
        );
        $this->assertEquals(
            $this->getTypeNode("array<Alias>"),
            $utils->resolveAlias(['type' => $this->getTypeNode("array<Alias>")])
        );
        $this->assertEquals([], $utils->testOnlyGetAliasCache());
    }

    public function testResolveEndpointImportAlias(): void {
        $utils = new PhpStanUtilsForTest();
        $class_name = FakePhpStanUtilsTypedEndpoint::class;
        $this->assertEquals(
            $this->getTypeNode("int"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedInt'])
        );
        $this->assertEquals(
            $this->getTypeNode("non-empty-string"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedUtil1String'])
        );
        $this->assertEquals(
            $this->getTypeNode("array<AliasedUtil1String>"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedUtil1StringArray'])
        );
        $this->assertEquals(
            $this->getTypeNode("array<AliasedUtil1Int>"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedUtil1IntArray'])
        );
        $this->assertEquals(
            $this->getTypeNode("array<AliasedUtil1IntArray>"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedUtil1IntArrayArray'])
        );
        $this->assertEquals([
            FakeUtil1::class => [
                'AliasedUtil1String' => ['type' => $this->getTypeNode('non-empty-string')],
                'AliasedUtil1IntArray' => ['type' => $this->getTypeNode('array<AliasedUtil1Int>')],
                'AliasedUtil2IntArray' => [
                    'namespace' => FakeUtil2::class,
                    'name' => 'AliasedUtil2IntArray',
                ],
                'AliasedUtil1Int' => [
                    'namespace' => FakeUtil2::class,
                    'name' => 'AliasedUtil2Int',
                ],
            ],
            FakePhpStanUtilsTypedEndpoint::class => [
                'AliasedInt' => ['type' => $this->getTypeNode('int')],
                'AliasedUtil1StringArray' => ['type' => $this->getTypeNode('array<AliasedUtil1String>')],
                'AliasedUtil1IntArrayArray' => ['type' => $this->getTypeNode('array<AliasedUtil1IntArray>')],
                'AliasedUtil1String' => [
                    'namespace' => FakeUtil1::class,
                    'name' => 'AliasedUtil1String',
                ],
                'AliasedUtil1IntArray' => [
                    'namespace' => FakeUtil1::class,
                    'name' => 'AliasedUtil1IntArray',
                ],
            ],
        ], $utils->testOnlyGetAliasCache());
    }

    public function testResolveUtilImportAlias(): void {
        $utils = new PhpStanUtilsForTest();
        $class_name = FakeUtil1::class;
        $this->assertEquals(
            $this->getTypeNode("non-empty-string"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedUtil1String'])
        );
        $this->assertEquals(
            $this->getTypeNode("int<2, max>"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedUtil1Int'])
        );
        $this->assertEquals(
            $this->getTypeNode("array<AliasedUtil1Int>"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'AliasedUtil1IntArray'])
        );
        $this->assertEquals([
            FakeUtil1::class => [
                'AliasedUtil1String' => ['type' => $this->getTypeNode('non-empty-string')],
                'AliasedUtil1IntArray' => ['type' => $this->getTypeNode('array<AliasedUtil1Int>')],
                'AliasedUtil2IntArray' => [
                    'namespace' => FakeUtil2::class,
                    'name' => 'AliasedUtil2IntArray',
                ],
                'AliasedUtil1Int' => [
                    'namespace' => FakeUtil2::class,
                    'name' => 'AliasedUtil2Int',
                ],
            ],
            FakeUtil2::class => [
                'AliasedUtil2Int' => ['type' => $this->getTypeNode('int<2, max>')],
                'AliasedUtil2IntArray' => ['type' => $this->getTypeNode('array<AliasedUtil2Int>')],
            ],
        ], $utils->testOnlyGetAliasCache());
    }

    public function testResolveFakeAliases(): void {
        $utils = new PhpStanUtilsForTest();
        $class_name = FakeAClass::class;
        $this->assertEquals(
            $this->getTypeNode("array{anotherA: FakeAnotherAType, aa: FakeAAType, b: FakeBType, top: FakeTopType, sameFileA: FakeSameFileAType}"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'FakeType'])
        );
        $this->assertEquals(
            $this->getTypeNode("string"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'FakeAnotherAType'])
        );
        $this->assertEquals(
            $this->getTypeNode("string"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'FakeAAType'])
        );
        $this->assertEquals(
            $this->getTypeNode("string"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'FakeBType'])
        );
        $this->assertEquals(
            $this->getTypeNode("string"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'FakeTopType'])
        );
        $this->assertEquals(
            $this->getTypeNode("string"),
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'FakeSameFileAType'])
        );
        $this->assertEquals([
            FakeAClass::class => [
                'FakeType' => ['type' => $this->getTypeNode(<<<'ZZZZZZZZZZ'
                    array{
                        anotherA: FakeAnotherAType,
                        aa: FakeAAType,
                        b: FakeBType,
                        top: FakeTopType,
                        sameFileA: FakeSameFileAType,
                    }
                    ZZZZZZZZZZ)],
                'FakeAnotherAType' => [
                    'namespace' => FakeAnotherAClass::class,
                    'name' => 'FakeAnotherAType',
                ],
                'FakeAAType' => [
                    'namespace' => FakeAAClass::class,
                    'name' => 'FakeAAType',
                ],
                'FakeBType' => [
                    'namespace' => FakeBClass::class,
                    'name' => 'FakeBType',
                ],
                'FakeTopType' => [
                    'namespace' => FakeTopClass::class,
                    'name' => 'FakeTopType',
                ],
                'FakeSameFileAType' => [
                    'namespace' => FakeSameFileAClass::class,
                    'name' => 'FakeSameFileAType',
                ],
            ],
            FakeAnotherAClass::class => [
                'FakeAnotherAType' => ['type' => $this->getTypeNode("string")],
            ],
            FakeAAClass::class => [
                'FakeAAType' => ['type' => $this->getTypeNode("string")],
            ],
            FakeBClass::class => [
                'FakeBType' => ['type' => $this->getTypeNode("string")],
            ],
            FakeTopClass::class => [
                'FakeTopType' => ['type' => $this->getTypeNode("string")],
            ],
            FakeSameFileAClass::class => [
                'FakeSameFileAType' => ['type' => $this->getTypeNode("string")],
            ],
        ], $utils->testOnlyGetAliasCache());
    }

    public function testResolveImportInfiniteLoop(): void {
        $utils = new PhpStanUtilsForTest();
        $class_name = FakeLoop1::class;
        try {
            $utils->resolveAlias(['namespace' => $class_name, 'name' => 'InfiniteLoop']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertEquals([
                FakeLoop1::class => [
                    'InfiniteLoop' => [
                        'namespace' => FakeLoop2::class,
                        'name' => 'InfiniteLoop',
                    ],
                ],
                FakeLoop2::class => [
                    'InfiniteLoop' => [
                        'namespace' => FakeLoop1::class,
                        'name' => 'InfiniteLoop',
                    ],
                ],
            ], $utils->testOnlyGetAliasCache());
            $this->assertSame(
                'Maximum recusion level (100) reached: Failed importing InfiniteLoop from PhpTypeScriptApi\Tests\UnitTests\PhpStan\FakeLoop1',
                $th->getMessage(),
            );
        }
    }

    public function testGetImportedAliasesError(): void {
        $utils = new PhpStanUtilsForTest();
        try {
            $utils->resolveAlias(['namespace' => 'Invalid', 'name' => 'AliasedInt']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertEquals([
                'Invalid' => [],
            ], $utils->testOnlyGetAliasCache());
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

        $this->assertEquals([
            'T' => ['type' => $this->getTypeNode("string")],
            'U' => ['type' => $this->getTypeNode("'foo'")],
            'V' => ['type' => $this->getTypeNode("int")],
            'W' => ['type' => $this->getTypeNode("int<0, max>")],
            // @phpstan-ignore-next-line argument.type
        ], $utils->getTemplateAliases($phpDocNode, $this->getTypeNode("A<string, 'foo'>")));
        $this->assertEquals([
            'T' => ['type' => $this->getTypeNode("string")],
            'U' => ['type' => $this->getTypeNode("'foo'")],
            'V' => ['type' => $this->getTypeNode("3")],
            'W' => ['type' => $this->getTypeNode("-1")],
            // @phpstan-ignore-next-line argument.type
        ], $utils->getTemplateAliases($phpDocNode, $this->getTypeNode("A<string, 'foo', 3, -1>")));
        // We don't do checks
        $this->assertEquals([
            'T' => ['type' => $this->getTypeNode("string")],
            'U' => ['type' => $this->getTypeNode("3")],
            'V' => ['type' => $this->getTypeNode("'foo'")],
            'W' => ['type' => $this->getTypeNode("'bar'")],
            // @phpstan-ignore-next-line argument.type
        ], $utils->getTemplateAliases($phpDocNode, $this->getTypeNode("A<string, 3, 'foo', 'bar'>")));
        try {
            // @phpstan-ignore-next-line argument.type
            $utils->getTemplateAliases($phpDocNode, $this->getTypeNode("A<'too few'>"));
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 2-4 generic arguments, but got 'A<'too few'>'",
                $th->getMessage(),
            );
        }
        try {
            // @phpstan-ignore-next-line argument.type
            $utils->getTemplateAliases($phpDocNode, $this->getTypeNode("A<'too', 'many', 'args', 'in', 'here'>"));
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 2-4 generic arguments, but got 'A<'too', 'many', 'args', 'in', 'here'>'",
                $th->getMessage(),
            );
        }

        // Null-related edge cases
        $this->assertEquals([], $utils->getTemplateAliases(null, null));
        try {
            $this->assertEquals([], $utils->getTemplateAliases($phpDocNode, null));
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                "Expected 2-4 generic arguments, but got '<>'",
                $th->getMessage(),
            );
        }
        // @phpstan-ignore-next-line argument.type
        $this->assertEquals([], $utils->getTemplateAliases(null, $this->getTypeNode("A<string, 'foo'>")));
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
