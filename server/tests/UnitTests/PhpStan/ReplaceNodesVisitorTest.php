<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\PhpStan\ReplaceNodesVisitor;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @phpstan-import-type Alias from PhpStanUtils
 *
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\ReplaceNodesVisitor
 */
final class ReplaceNodesVisitorTest extends UnitTestCase {
    public function testResolvesAliasedIntNode(): void {
        $this->assertSame('int', $this->replaceIdentifiers('AliasedInt'));
    }

    public function testResolvesAliasedIntNodeInGeneric(): void {
        $this->assertSame('int<0, 100>', $this->replaceIdentifiers('int<0, 100>'));
        $this->assertSame('int<min, 100>', $this->replaceIdentifiers('int<min, 100>'));
        $this->assertSame('int<50, max>', $this->replaceIdentifiers('int<50, max>'));
    }

    public function testResolvesAliasedIntNodeInUnion(): void {
        $this->assertSame("(int | 'bar')", $this->replaceIdentifiers("AliasedInt|'bar'"));
        $this->assertSame("(null | int | 'bar')", $this->replaceIdentifiers("null|AliasedInt|'bar'"));
        $this->assertSame("?(int | 'bar')", $this->replaceIdentifiers("?(AliasedInt|'bar')"));
    }

    public function testResolvesAliasedIntNodeInArrayNode(): void {
        $this->assertSame("array<int>", $this->replaceIdentifiers("array<AliasedInt>"));
        $this->assertSame("array<?int>", $this->replaceIdentifiers("array<?AliasedInt>"));
        $this->assertSame("non-empty-array<int>", $this->replaceIdentifiers("non-empty-array<AliasedInt>"));
    }

    public function testResolvesAliasedIntNodeInDictNode(): void {
        $this->assertSame("array<int, string>", $this->replaceIdentifiers("array<AliasedInt, string>"));
        $this->assertSame("array<bool, ?int>", $this->replaceIdentifiers("array<bool, ?AliasedInt>"));
        $this->assertSame("array<?string, int>", $this->replaceIdentifiers("array<?string, AliasedInt>"));
        $this->assertSame("non-empty-array<?int, string>", $this->replaceIdentifiers("non-empty-array<?AliasedInt, string>"));
    }

    public function testResolvesAliasedIntNodeInObjectNode(): void {
        $this->assertSame(
            "array{'foo': int, \"bar\": string}",
            $this->replaceIdentifiers("array{'foo': AliasedInt, \"bar\": string}"),
        );
        $this->assertSame(
            "array{'foo': int, 'bar'?: string}",
            $this->replaceIdentifiers("array{'foo': AliasedInt, 'bar'?: string}"),
        );
        $this->assertSame(
            "array{int, int}",
            $this->replaceIdentifiers("array{AliasedInt, AliasedInt}"),
        );
        $this->assertSame(
            "array{0: int, 1?: int}",
            $this->replaceIdentifiers("array{0: AliasedInt, 1?: AliasedInt}"),
        );
        $this->assertSame(
            "array{foo: int, bar: string}",
            $this->replaceIdentifiers("array{foo: AliasedInt, bar: string}"),
        );
        $this->assertSame(
            "object{'foo': int, \"bar\": string}",
            $this->replaceIdentifiers("object{'foo': AliasedInt, \"bar\": string}"),
        );
    }

    public function testResolvesAliasedObjectNode(): void {
        $this->assertSame('array{foo: AliasedInt, bar?: string}', $this->replaceIdentifiers('AliasedObject'));
    }

    public function testResolvesAliasNamespace(): void {
        $this->assertSame('null', $this->replaceIdentifiers('Aliased_4'));
    }

    public function testSkipsIsoDateNode(): void {
        $this->assertSame('IsoDate', $this->replaceIdentifiers('IsoDate'));
    }

    public function testSkipsIsoDateFullNode(): void {
        $class = IsoDate::class;
        $this->assertSame(
            'PhpTypeScriptApi\PhpStan\IsoDate',
            $this->replaceIdentifiers("{$class}")
        );
    }

    public function testSkipsIsoDateFullyQualifiedNode(): void {
        $class = IsoDate::class;
        $this->assertSame(
            '\PhpTypeScriptApi\PhpStan\IsoDate',
            $this->replaceIdentifiers("\\{$class}"),
        );
    }

    public function testSkipsUnsupportedNamedTypeNode(): void {
        $this->assertSame('Invalid', $this->replaceIdentifiers('Invalid'));
    }

    public function testSkipsOtherNodes(): void {
        $this->assertSame('mixed', $this->replaceIdentifiers('mixed'));
        $this->assertSame('null', $this->replaceIdentifiers('null'));
        $this->assertSame('bool', $this->replaceIdentifiers('bool'));
        $this->assertSame('boolean', $this->replaceIdentifiers('boolean'));
        $this->assertSame('true', $this->replaceIdentifiers('true'));
        $this->assertSame('false', $this->replaceIdentifiers('false'));
        $this->assertSame('int', $this->replaceIdentifiers('int'));
        $this->assertSame('positive-int', $this->replaceIdentifiers('positive-int'));
        $this->assertSame('negative-int', $this->replaceIdentifiers('negative-int'));
        $this->assertSame('non-positive-int', $this->replaceIdentifiers('non-positive-int'));
        $this->assertSame('non-negative-int', $this->replaceIdentifiers('non-negative-int'));
        $this->assertSame('non-zero-int', $this->replaceIdentifiers('non-zero-int'));
        $this->assertSame('int<0, 100>', $this->replaceIdentifiers('int<0, 100>'));
        $this->assertSame('int<min, 100>', $this->replaceIdentifiers('int<min, 100>'));
        $this->assertSame('int<50, max>', $this->replaceIdentifiers('int<50, max>'));
        $this->assertSame('float', $this->replaceIdentifiers('float'));
        $this->assertSame('double', $this->replaceIdentifiers('double'));
        $this->assertSame('number', $this->replaceIdentifiers('number'));
        $this->assertSame('scalar', $this->replaceIdentifiers('scalar'));
        $this->assertSame('string', $this->replaceIdentifiers('string'));
        $this->assertSame('class-string', $this->replaceIdentifiers('class-string'));
        $this->assertSame('class-string<T>', $this->replaceIdentifiers('class-string<T>'));
        $this->assertSame('callable-string', $this->replaceIdentifiers('callable-string'));
        $this->assertSame('numeric-string', $this->replaceIdentifiers('numeric-string'));
        $this->assertSame('non-empty-string', $this->replaceIdentifiers('non-empty-string'));
        $this->assertSame('non-falsy-string', $this->replaceIdentifiers('non-falsy-string'));
        $this->assertSame('truthy-string', $this->replaceIdentifiers('truthy-string'));
        $this->assertSame('literal-string', $this->replaceIdentifiers('literal-string'));
        $this->assertSame('lowercase-string', $this->replaceIdentifiers('lowercase-string'));
        $this->assertSame('array-key', $this->replaceIdentifiers('array-key'));
        $this->assertSame("('foo' | 'bar')", $this->replaceIdentifiers("'foo'|'bar'"));
        $this->assertSame("array", $this->replaceIdentifiers("array"));
        $this->assertSame("object", $this->replaceIdentifiers("object"));
        $this->assertSame("array{}", $this->replaceIdentifiers("array{}"));
        $this->assertSame("object{}", $this->replaceIdentifiers("object{}"));
        $this->assertSame('$this', $this->replaceIdentifiers('$this'));
    }

    public function testReplaceNodesVisitorLeavesAliasesUnmodified(): void {
        $type_node = $this->getTypeNode('AliasedObject');
        $map = [
            'AliasedInt' => 'int',
            'AliasedObject' => 'array{foo: AliasedInt, bar?: string}',
        ];

        $new_type_node = $this->replaceIdentifiers($type_node, $map);

        $this->assertSame('array{foo: AliasedInt, bar?: string}', "{$new_type_node}");
        $this->assertEquals([
            'AliasedInt' => 'int',
            'AliasedObject' => 'array{foo: AliasedInt, bar?: string}',
        ], $map);
    }

    /**
     * @param ?array<string, string> $map
     */
    private function replaceIdentifiers(string|TypeNode $type, ?array $map = null): string {
        if ($type instanceof TypeNode) {
            $type_node = $type;
        } else {
            $type_node = $this->getTypeNode($type);
        }

        $map ??= [
            'AliasedInt' => 'int',
            'AliasedObject' => 'array{foo: AliasedInt, bar?: string}',
            'Aliased_4' => 'null',
        ];

        $visitor = new ReplaceNodesVisitor(function (Node $node) use ($map) {
            if (
                $node instanceof IdentifierTypeNode
                && isset($map[$node->name])
            ) {
                return new IdentifierTypeNode($map[$node->name]);
            }
            return $node;
        });
        $traverser = new NodeTraverser([$visitor]);
        try {
            [$new_type_node] = $traverser->traverse([$type_node]);
            return "{$new_type_node}";
        } catch (\Throwable $th) {
            return "🛑{$th->getMessage()}";
        }
    }
}
