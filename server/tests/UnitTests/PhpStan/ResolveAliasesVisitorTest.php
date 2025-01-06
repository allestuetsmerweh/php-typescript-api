<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\ResolveAliasesVisitor;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\ResolveAliasesVisitor
 */
final class ResolveAliasesVisitorTest extends UnitTestCase {
    public function testResolvesAliasedIntNode(): void {
        $this->assertSame('int', $this->resolveAliases('AliasedInt'));
    }

    public function testResolvesAliasedIntNodeInGeneric(): void {
        $this->assertSame('int<0, 100>', $this->resolveAliases('int<0, 100>'));
        $this->assertSame('int<min, 100>', $this->resolveAliases('int<min, 100>'));
        $this->assertSame('int<50, max>', $this->resolveAliases('int<50, max>'));
    }

    public function testResolvesAliasedIntNodeInUnion(): void {
        $this->assertSame("(int | 'bar')", $this->resolveAliases("AliasedInt|'bar'"));
        $this->assertSame("(null | int | 'bar')", $this->resolveAliases("null|AliasedInt|'bar'"));
        $this->assertSame("?(int | 'bar')", $this->resolveAliases("?(AliasedInt|'bar')"));
    }

    public function testResolvesAliasedIntNodeInArrayNode(): void {
        $this->assertSame("array<int>", $this->resolveAliases("array<AliasedInt>"));
        $this->assertSame("array<?int>", $this->resolveAliases("array<?AliasedInt>"));
        $this->assertSame("non-empty-array<int>", $this->resolveAliases("non-empty-array<AliasedInt>"));
    }

    public function testResolvesAliasedIntNodeInDictNode(): void {
        $this->assertSame("array<int, string>", $this->resolveAliases("array<AliasedInt, string>"));
        $this->assertSame("array<bool, ?int>", $this->resolveAliases("array<bool, ?AliasedInt>"));
        $this->assertSame("array<?string, int>", $this->resolveAliases("array<?string, AliasedInt>"));
        $this->assertSame("non-empty-array<?int, string>", $this->resolveAliases("non-empty-array<?AliasedInt, string>"));
    }

    public function testResolvesAliasedIntNodeInObjectNode(): void {
        $this->assertSame(
            "array{'foo': int, \"bar\": string}",
            $this->resolveAliases("array{'foo': AliasedInt, \"bar\": string}"),
        );
        $this->assertSame(
            "array{'foo': int, 'bar'?: string}",
            $this->resolveAliases("array{'foo': AliasedInt, 'bar'?: string}"),
        );
        $this->assertSame(
            "array{int, int}",
            $this->resolveAliases("array{AliasedInt, AliasedInt}"),
        );
        $this->assertSame(
            "array{0: int, 1?: int}",
            $this->resolveAliases("array{0: AliasedInt, 1?: AliasedInt}"),
        );
        $this->assertSame(
            "array{foo: int, bar: string}",
            $this->resolveAliases("array{foo: AliasedInt, bar: string}"),
        );
        $this->assertSame(
            "object{'foo': int, \"bar\": string}",
            $this->resolveAliases("object{'foo': AliasedInt, \"bar\": string}"),
        );
    }

    public function testResolvesAliasedObjectNode(): void {
        $this->assertSame('array{foo: int, bar?: string}', $this->resolveAliases('AliasedObject'));
    }

    public function testResolvesAliasNamespace(): void {
        $this->assertSame('null', $this->resolveAliases('Aliased_4'));
    }

    public function testSkipsIsoDateNode(): void {
        $this->assertSame('IsoDate', $this->resolveAliases('IsoDate'));
    }

    public function testSkipsIsoDateFullNode(): void {
        $class = IsoDate::class;
        $this->assertSame(
            'PhpTypeScriptApi\PhpStan\IsoDate',
            $this->resolveAliases("{$class}")
        );
    }

    public function testSkipsIsoDateFullyQualifiedNode(): void {
        $class = IsoDate::class;
        $this->assertSame(
            '\PhpTypeScriptApi\PhpStan\IsoDate',
            $this->resolveAliases("\\{$class}"),
        );
    }

    public function testSkipsUnsupportedNamedTypeNode(): void {
        $this->assertSame('Invalid', $this->resolveAliases('Invalid'));
    }

    public function testSkipsOtherNodes(): void {
        $this->assertSame('mixed', $this->resolveAliases('mixed'));
        $this->assertSame('null', $this->resolveAliases('null'));
        $this->assertSame('bool', $this->resolveAliases('bool'));
        $this->assertSame('boolean', $this->resolveAliases('boolean'));
        $this->assertSame('true', $this->resolveAliases('true'));
        $this->assertSame('false', $this->resolveAliases('false'));
        $this->assertSame('int', $this->resolveAliases('int'));
        $this->assertSame('positive-int', $this->resolveAliases('positive-int'));
        $this->assertSame('negative-int', $this->resolveAliases('negative-int'));
        $this->assertSame('non-positive-int', $this->resolveAliases('non-positive-int'));
        $this->assertSame('non-negative-int', $this->resolveAliases('non-negative-int'));
        $this->assertSame('non-zero-int', $this->resolveAliases('non-zero-int'));
        $this->assertSame('int<0, 100>', $this->resolveAliases('int<0, 100>'));
        $this->assertSame('int<min, 100>', $this->resolveAliases('int<min, 100>'));
        $this->assertSame('int<50, max>', $this->resolveAliases('int<50, max>'));
        $this->assertSame('float', $this->resolveAliases('float'));
        $this->assertSame('double', $this->resolveAliases('double'));
        $this->assertSame('number', $this->resolveAliases('number'));
        $this->assertSame('scalar', $this->resolveAliases('scalar'));
        $this->assertSame('string', $this->resolveAliases('string'));
        $this->assertSame('class-string', $this->resolveAliases('class-string'));
        $this->assertSame('class-string<T>', $this->resolveAliases('class-string<T>'));
        $this->assertSame('callable-string', $this->resolveAliases('callable-string'));
        $this->assertSame('numeric-string', $this->resolveAliases('numeric-string'));
        $this->assertSame('non-empty-string', $this->resolveAliases('non-empty-string'));
        $this->assertSame('non-falsy-string', $this->resolveAliases('non-falsy-string'));
        $this->assertSame('truthy-string', $this->resolveAliases('truthy-string'));
        $this->assertSame('literal-string', $this->resolveAliases('literal-string'));
        $this->assertSame('lowercase-string', $this->resolveAliases('lowercase-string'));
        $this->assertSame('array-key', $this->resolveAliases('array-key'));
        $this->assertSame("('foo' | 'bar')", $this->resolveAliases("'foo'|'bar'"));
        $this->assertSame("array", $this->resolveAliases("array"));
        $this->assertSame("object", $this->resolveAliases("object"));
        $this->assertSame("array{}", $this->resolveAliases("array{}"));
        $this->assertSame("object{}", $this->resolveAliases("object{}"));
        $this->assertSame('$this', $this->resolveAliases('$this'));
    }

    public function testResolveAliasesVisitorLeavesAliasesUnmodified(): void {
        $type_node = $this->getTypeNode('AliasedObject');
        $aliases = [
            'AliasedInt' => $this->getTypeNode('int'),
            'AliasedObject' => $this->getTypeNode('array{foo: AliasedInt, bar?: string}'),
        ];

        $visitor = new ResolveAliasesVisitor($aliases);
        $traverser = new NodeTraverser([$visitor]);
        [$new_type_node] = $traverser->traverse([$type_node]);

        $this->assertSame('array{foo: int, bar?: string}', "{$new_type_node}");
        $this->assertEquals([
            'AliasedInt' => $this->getTypeNode('int'),
            'AliasedObject' => $this->getTypeNode('array{foo: AliasedInt, bar?: string}'),
        ], $aliases);
    }

    private function resolveAliases(string|TypeNode $type): string {
        if ($type instanceof TypeNode) {
            $type_node = $type;
        } else {
            $type_node = $this->getTypeNode($type);
        }

        $aliases = [
            'AliasedInt' => $this->getTypeNode('int'),
            'AliasedObject' => $this->getTypeNode('array{foo: AliasedInt, bar?: string}'),
            'Aliased_4' => $this->getTypeNode('null'),
        ];

        $visitor = new ResolveAliasesVisitor($aliases);
        $traverser = new NodeTraverser([$visitor]);
        try {
            [$new_type_node] = $traverser->traverse([$type_node]);
            return "{$new_type_node}";
        } catch (\Throwable $th) {
            return "🛑{$th->getMessage()}";
        }
    }
}
