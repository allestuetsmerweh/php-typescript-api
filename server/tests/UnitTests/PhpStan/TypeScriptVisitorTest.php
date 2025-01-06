<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\IsoDateTime;
use PhpTypeScriptApi\PhpStan\IsoTime;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\PhpStan\TypeScriptVisitor;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\TypeScriptVisitor
 */
final class TypeScriptVisitorTest extends UnitTestCase {
    public function testMixedNode(): void {
        $this->assertSame('unknown', $this->getTypeScript('mixed'));
    }

    public function testNullNode(): void {
        $this->assertSame('null', $this->getTypeScript('null'));
    }

    public function testBooleanNode(): void {
        $this->assertSame('boolean', $this->getTypeScript('bool'));
        $this->assertSame('boolean', $this->getTypeScript('boolean'));
        $this->assertSame('true', $this->getTypeScript('true'));
        $this->assertSame('false', $this->getTypeScript('false'));
    }

    public function testIntNode(): void {
        $this->assertSame('number', $this->getTypeScript('int'));
        $this->assertSame('number', $this->getTypeScript('positive-int'));
        $this->assertSame('number', $this->getTypeScript('negative-int'));
        $this->assertSame('number', $this->getTypeScript('non-positive-int'));
        $this->assertSame('number', $this->getTypeScript('non-negative-int'));
        $this->assertSame('number', $this->getTypeScript('non-zero-int'));
        $this->assertSame('number', $this->getTypeScript('int<0, 100>'));
        $this->assertSame('number', $this->getTypeScript('int<min, 100>'));
        $this->assertSame('number', $this->getTypeScript('int<50, max>'));
        $this->assertSame('number', $this->getTypeScript('float'));
        $this->assertSame('number', $this->getTypeScript('double'));
        $this->assertSame('number', $this->getTypeScript('number'));
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: scalar',
            $this->getTypeScript('scalar'),
        );
    }

    public function testStringNode(): void {
        $this->assertSame('string', $this->getTypeScript('string'));
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: class-string',
            $this->getTypeScript('class-string'),
        );
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: class-string',
            $this->getTypeScript('class-string<T>'),
        );
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: callable-string',
            $this->getTypeScript('callable-string'),
        );
        $this->assertSame('string', $this->getTypeScript('numeric-string'));
        $this->assertSame('string', $this->getTypeScript('non-empty-string'));
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: non-falsy-string',
            $this->getTypeScript('non-falsy-string'),
        );
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: truthy-string',
            $this->getTypeScript('truthy-string'),
        );
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: literal-string',
            $this->getTypeScript('literal-string'),
        );
        $this->assertSame('string', $this->getTypeScript('lowercase-string'));
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: array-key',
            $this->getTypeScript('array-key'),
        );
    }

    public function testEnumNode(): void {
        $this->assertSame("('foo' | 'bar')", $this->getTypeScript("'foo'|'bar'"));
        $this->assertSame("(null | 'foo' | 'bar')", $this->getTypeScript("null|'foo'|'bar'"));
        $this->assertSame("(('foo' | 'bar') | null)", $this->getTypeScript("?('foo'|'bar')"));
    }

    public function testArrayNode(): void {
        $this->assertSame("Array", $this->getTypeScript("array")); // TODO: Not sure...
        $this->assertSame("Array<boolean>", $this->getTypeScript("array<bool>"));
        $this->assertSame("Array<(number | null)>", $this->getTypeScript("array<?int>"));
        $this->assertSame("Array<string>", $this->getTypeScript("array<string>"));
        $this->assertSame("Array<string>", $this->getTypeScript("non-empty-array<string>"));
    }

    public function testDictNode(): void {
        $this->assertSame("{[key: number]: string}", $this->getTypeScript("array<int, string>"));
        $this->assertSame("{[key: boolean]: (string | null)}", $this->getTypeScript("array<bool, ?string>"));
        $this->assertSame("{[key: (string | null)]: boolean}", $this->getTypeScript("array<?string, bool>"));
        $this->assertSame("{[key: (number | null)]: string}", $this->getTypeScript("non-empty-array<?float, string>"));
    }

    public function testObjectNode(): void {
        $this->assertSame("Array", $this->getTypeScript("object")); // TODO: Not sure...
        $this->assertSame(
            "{'foo': number, \"bar\": string}",
            $this->getTypeScript("array{'foo': int, \"bar\": string}"),
        );
        $this->assertSame(
            "{'foo': number, 'bar'?: string}",
            $this->getTypeScript("array{'foo': int, 'bar'?: string}"),
        );
        $this->assertSame(
            "{number, number}",
            $this->getTypeScript("array{int, int}"),
        );
        $this->assertSame(
            "{0: number, 1?: number}",
            $this->getTypeScript("array{0: int, 1?: int}"),
        );
        $this->assertSame(
            "{'foo': number, 'bar': string}",
            $this->getTypeScript("array{foo: int, bar: string}"),
        );
        $this->assertSame(
            "{'foo': number, \"bar\": string}",
            $this->getTypeScript("object{'foo': int, \"bar\": string}"),
        );
        $this->assertSame("Record<string, never>", $this->getTypeScript("array{}"));
        $this->assertSame("Record<string, never>", $this->getTypeScript("object{}"));
    }

    public function testAliasedIntNode(): void {
        $this->assertSame('AliasedInt', $this->getTypeScript('AliasedInt'));
    }

    public function testAliasedObjectNode(): void {
        $this->assertSame('AliasedObject', $this->getTypeScript('AliasedObject'));
    }

    public function testAliasNamespace(): void {
        $this->assertSame('Aliased_4', $this->getTypeScript('Aliased_4'));
    }

    public function testIsoDateNode(): void {
        $this->assertSame('IsoDate', $this->getTypeScript('IsoDate'));
    }

    public function testIsoDateFullNode(): void {
        $class = IsoDate::class;
        $this->assertSame(
            'PhpTypeScriptApi_PhpStan_IsoDate',
            $this->getTypeScript("{$class}")
        );
    }

    public function testIsoDateFullyQualifiedNode(): void {
        $class = IsoDate::class;
        $this->assertSame(
            '_PhpTypeScriptApi_PhpStan_IsoDate',
            $this->getTypeScript("\\{$class}"),
        );
    }

    public function testUnsupportedNamedTypeNode(): void {
        $this->assertSame(
            '🛑Unknown IdentifierTypeNode name: Invalid',
            $this->getTypeScript('Invalid'),
        );
    }

    public function testUnsupportedNodes(): void {
        $this->assertSame(
            '🛑leaveNode: Unknown node class: PHPStan\PhpDocParser\Ast\Type\ThisTypeNode',
            $this->getTypeScript(new ThisTypeNode())
        );
    }

    private function getTypeScript(string|TypeNode $type): string {
        if ($type instanceof TypeNode) {
            $type_node = $type;
        } else {
            $type_node = $this->getTypeNode($type);
        }

        $aliases = [
            'AliasedInt' => $this->getTypeNode('int'),
            'AliasedObject' => $this->getTypeNode('array{foo: int, bar?: string}'),
            'Aliased_4' => $this->getTypeNode('null'),
        ];
        PhpStanUtils::registerApiObject(IsoDate::class);
        PhpStanUtils::registerApiObject(IsoDateTime::class);
        PhpStanUtils::registerApiObject(IsoTime::class);

        $visitor = new TypeScriptVisitor($aliases);
        $traverser = new NodeTraverser([$visitor]);
        try {
            [$ts_type_node] = $traverser->traverse([$type_node]);
            return "{$ts_type_node}";
        } catch (\Throwable $th) {
            return "🛑{$th->getMessage()}";
        }
    }
}
