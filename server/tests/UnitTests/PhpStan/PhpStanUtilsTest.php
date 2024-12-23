<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\PhpStanUtils
 */
final class PhpStanUtilsTest extends UnitTestCase {
    public function testParseValidDocComment(): void {
        $utils = new PhpStanUtils();
        $comment = <<<'ZZZZZZZZZZ'
            /**
             * @param string $arg0
             * @return int
             */
            ZZZZZZZZZZ;

        $phpDocNode = $utils->parseDocComment($comment);

        $this->assertSame('string', "{$phpDocNode->getParamTagValues()[0]->type}");
        $this->assertSame('int', "{$phpDocNode->getReturnTagValues()[0]->type}");
    }

    public function testParseEmptyDocComment(): void {
        $utils = new PhpStanUtils();

        $phpDocNode = $utils->parseDocComment('/** Empty */');

        $this->assertCount(0, $phpDocNode->getParamTagValues());
        $this->assertCount(0, $phpDocNode->getReturnTagValues());
    }

    public function testParseInexistentDocComment(): void {
        $utils = new PhpStanUtils();

        try {
            $utils->parseDocComment(false);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('Cannot parse doc comment.', $th->getMessage());
        }
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

    public function testGetNamedIntTypeNode(): void {
        $utils = new PhpStanUtils();

        $typeNode = $utils->getNamedTypeNode(new \ReflectionClass(NamedInt::class));

        $this->assertSame('int', "{$typeNode}");
    }

    public function testGetNamedTypeNodeInvalidClass(): void {
        $utils = new PhpStanUtils();

        try {
            // @phpstan-ignore argument.type
            $utils->getNamedTypeNode(new \ReflectionClass('invalid'));
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('Class "invalid" does not exist', $th->getMessage());
        }
    }

    public function testGetNamedTypeNodeNotNamedType(): void {
        $utils = new PhpStanUtils();

        try {
            $utils->getNamedTypeNode(new \ReflectionClass(self::class));
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Only classes directly extending NamedType may be used.',
                $th->getMessage(),
            );
        }
    }
}
