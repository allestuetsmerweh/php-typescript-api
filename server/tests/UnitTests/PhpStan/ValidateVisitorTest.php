<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\PhpStan\ValidateVisitor;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\ValidateVisitor
 */
final class ValidateVisitorTest extends UnitTestCase {
    public function testMixedNode(): void {
        $this->assertNull($this->validate('mixed', null));
        $this->assertNull($this->validate('mixed', true));
        $this->assertNull($this->validate('mixed', 2));
        $this->assertNull($this->validate('mixed', 'text'));
        $this->assertNull($this->validate('mixed', [1, 2, 3]));
        $this->assertNull($this->validate('mixed', ['foo' => 'bar']));
    }

    public function testNullNode(): void {
        $this->assertNull($this->validate('null', null));
        $this->assertNotNull($this->validate('null', true));
        $this->assertNotNull($this->validate('null', 2));
        $this->assertNotNull($this->validate('null', 'text'));
        $this->assertNotNull($this->validate('null', [1, 2, 3]));
        $this->assertNotNull($this->validate('null', ['foo' => 'bar']));
    }

    public function testBoolNode(): void {
        $this->assertNotNull($this->validate('bool', null));
        $this->assertNull($this->validate('bool', true));
        $this->assertNull($this->validate('bool', false));
        $this->assertNotNull($this->validate('bool', 2));
        $this->assertNotNull($this->validate('bool', 'text'));
        $this->assertNotNull($this->validate('bool', [1, 2, 3]));
        $this->assertNotNull($this->validate('bool', ['foo' => 'bar']));
    }

    public function testBooleanNode(): void {
        $this->assertNotNull($this->validate('boolean', null));
        $this->assertNull($this->validate('boolean', true));
        $this->assertNull($this->validate('boolean', false));
        $this->assertNotNull($this->validate('boolean', 2));
        $this->assertNotNull($this->validate('boolean', 'text'));
        $this->assertNotNull($this->validate('boolean', [1, 2, 3]));
        $this->assertNotNull($this->validate('boolean', ['foo' => 'bar']));
    }

    public function testUnsupportedBooleanNodes(): void {
        try {
            $this->validate('false', false);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: false',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('true', true);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: true',
                $th->getMessage(),
            );
        }
    }

    public function testIntNode(): void {
        $this->assertNotNull($this->validate('int', null));
        $this->assertNotNull($this->validate('int', true));
        $this->assertNull($this->validate('int', PHP_INT_MIN));
        $this->assertNull($this->validate('int', -100));
        $this->assertNull($this->validate('int', 0));
        $this->assertNull($this->validate('int', 2));
        $this->assertNull($this->validate('int', PHP_INT_MAX));
        $this->assertNotNull($this->validate('int', '5'));
        $this->assertNotNull($this->validate('int', 3.14));
        $this->assertNotNull($this->validate('int', 7.64E+5));
        $this->assertNotNull($this->validate('int', INF));
        $this->assertNotNull($this->validate('int', NAN));
        $this->assertNotNull($this->validate('int', 'text'));
        $this->assertNotNull($this->validate('int', [1, 2, 3]));
        $this->assertNotNull($this->validate('int', ['foo' => 'bar']));
    }

    public function testPositiveIntNode(): void {
        $this->assertNotNull($this->validate('positive-int', null));
        $this->assertNotNull($this->validate('positive-int', true));
        $this->assertNotNull($this->validate('positive-int', PHP_INT_MIN));
        $this->assertNotNull($this->validate('positive-int', -100));
        $this->assertNotNull($this->validate('positive-int', 0));
        $this->assertNull($this->validate('positive-int', 2));
        $this->assertNull($this->validate('positive-int', PHP_INT_MAX));
        $this->assertNotNull($this->validate('positive-int', '5'));
        $this->assertNotNull($this->validate('positive-int', 3.14));
        $this->assertNotNull($this->validate('positive-int', 7.64E+5));
        $this->assertNotNull($this->validate('positive-int', INF));
        $this->assertNotNull($this->validate('positive-int', NAN));
        $this->assertNotNull($this->validate('positive-int', 'text'));
        $this->assertNotNull($this->validate('positive-int', [1, 2, 3]));
        $this->assertNotNull($this->validate('positive-int', ['foo' => 'bar']));
    }

    public function testNegativeIntNode(): void {
        $this->assertNotNull($this->validate('negative-int', null));
        $this->assertNotNull($this->validate('negative-int', true));
        $this->assertNull($this->validate('negative-int', PHP_INT_MIN));
        $this->assertNull($this->validate('negative-int', -100));
        $this->assertNotNull($this->validate('negative-int', 0));
        $this->assertNotNull($this->validate('negative-int', 2));
        $this->assertNotNull($this->validate('negative-int', PHP_INT_MAX));
        $this->assertNotNull($this->validate('negative-int', '5'));
        $this->assertNotNull($this->validate('negative-int', 3.14));
        $this->assertNotNull($this->validate('negative-int', 7.64E+5));
        $this->assertNotNull($this->validate('negative-int', INF));
        $this->assertNotNull($this->validate('negative-int', NAN));
        $this->assertNotNull($this->validate('negative-int', 'text'));
        $this->assertNotNull($this->validate('negative-int', [1, 2, 3]));
        $this->assertNotNull($this->validate('negative-int', ['foo' => 'bar']));
    }

    public function testNonPositiveIntNode(): void {
        $this->assertNotNull($this->validate('non-positive-int', null));
        $this->assertNotNull($this->validate('non-positive-int', true));
        $this->assertNull($this->validate('non-positive-int', PHP_INT_MIN));
        $this->assertNull($this->validate('non-positive-int', -100));
        $this->assertNull($this->validate('non-positive-int', 0));
        $this->assertNotNull($this->validate('non-positive-int', 2));
        $this->assertNotNull($this->validate('non-positive-int', PHP_INT_MAX));
        $this->assertNotNull($this->validate('non-positive-int', '5'));
        $this->assertNotNull($this->validate('non-positive-int', 3.14));
        $this->assertNotNull($this->validate('non-positive-int', 7.64E+5));
        $this->assertNotNull($this->validate('non-positive-int', INF));
        $this->assertNotNull($this->validate('non-positive-int', NAN));
        $this->assertNotNull($this->validate('non-positive-int', 'text'));
        $this->assertNotNull($this->validate('non-positive-int', [1, 2, 3]));
        $this->assertNotNull($this->validate('non-positive-int', ['foo' => 'bar']));
    }

    public function testNonNegativeIntNode(): void {
        $this->assertNotNull($this->validate('non-negative-int', null));
        $this->assertNotNull($this->validate('non-negative-int', true));
        $this->assertNotNull($this->validate('non-negative-int', PHP_INT_MIN));
        $this->assertNotNull($this->validate('non-negative-int', -100));
        $this->assertNull($this->validate('non-negative-int', 0));
        $this->assertNull($this->validate('non-negative-int', 2));
        $this->assertNull($this->validate('non-negative-int', PHP_INT_MAX));
        $this->assertNotNull($this->validate('non-negative-int', '5'));
        $this->assertNotNull($this->validate('non-negative-int', 3.14));
        $this->assertNotNull($this->validate('non-negative-int', 7.64E+5));
        $this->assertNotNull($this->validate('non-negative-int', INF));
        $this->assertNotNull($this->validate('non-negative-int', NAN));
        $this->assertNotNull($this->validate('non-negative-int', 'text'));
        $this->assertNotNull($this->validate('non-negative-int', [1, 2, 3]));
        $this->assertNotNull($this->validate('non-negative-int', ['foo' => 'bar']));
    }

    public function testNonZeroIntNode(): void {
        $this->assertNotNull($this->validate('non-zero-int', null));
        $this->assertNotNull($this->validate('non-zero-int', true));
        $this->assertNull($this->validate('non-zero-int', PHP_INT_MIN));
        $this->assertNull($this->validate('non-zero-int', -100));
        $this->assertNotNull($this->validate('non-zero-int', 0));
        $this->assertNull($this->validate('non-zero-int', 2));
        $this->assertNull($this->validate('non-zero-int', PHP_INT_MAX));
        $this->assertNotNull($this->validate('non-zero-int', '5'));
        $this->assertNotNull($this->validate('non-zero-int', 3.14));
        $this->assertNotNull($this->validate('non-zero-int', 7.64E+5));
        $this->assertNotNull($this->validate('non-zero-int', INF));
        $this->assertNotNull($this->validate('non-zero-int', NAN));
        $this->assertNotNull($this->validate('non-zero-int', 'text'));
        $this->assertNotNull($this->validate('non-zero-int', [1, 2, 3]));
        $this->assertNotNull($this->validate('non-zero-int', ['foo' => 'bar']));
    }

    public function testConstIntNode(): void {
        $this->assertNotNull($this->validate('3', null));
        $this->assertNotNull($this->validate('3', true));
        $this->assertNotNull($this->validate('3', PHP_INT_MIN));
        $this->assertNotNull($this->validate('3', -3));
        $this->assertNotNull($this->validate('3', 0));
        $this->assertNull($this->validate('3', 3));
        $this->assertNotNull($this->validate('3', PHP_INT_MAX));
        $this->assertNotNull($this->validate('3', '5'));
        $this->assertNotNull($this->validate('3', 3.14));
        $this->assertNotNull($this->validate('3', 7.64E+5));
        $this->assertNotNull($this->validate('3', INF));
        $this->assertNotNull($this->validate('3', NAN));
        $this->assertNotNull($this->validate('3', 'text'));
        $this->assertNotNull($this->validate('3', [1, 2, 3]));
        $this->assertNotNull($this->validate('3', ['foo' => 'bar']));
    }

    public function testFloatNode(): void {
        $this->assertNotNull($this->validate('float', null));
        $this->assertNotNull($this->validate('float', true));
        $this->assertNotNull($this->validate('float', PHP_INT_MIN));
        $this->assertNotNull($this->validate('float', -100));
        $this->assertNotNull($this->validate('float', 0));
        $this->assertNotNull($this->validate('float', 2));
        $this->assertNotNull($this->validate('float', PHP_INT_MAX));
        $this->assertNotNull($this->validate('float', '5'));
        $this->assertNull($this->validate('float', 3.14));
        $this->assertNull($this->validate('float', 7.64E+5));
        $this->assertNull($this->validate('float', INF));
        $this->assertNull($this->validate('float', NAN));
        $this->assertNotNull($this->validate('float', 'text'));
        $this->assertNotNull($this->validate('float', [1, 2, 3]));
        $this->assertNotNull($this->validate('float', ['foo' => 'bar']));
    }

    public function testDoubleNode(): void {
        $this->assertNotNull($this->validate('double', null));
        $this->assertNotNull($this->validate('double', true));
        $this->assertNotNull($this->validate('double', PHP_INT_MIN));
        $this->assertNotNull($this->validate('double', -100));
        $this->assertNotNull($this->validate('double', 0));
        $this->assertNotNull($this->validate('double', 2));
        $this->assertNotNull($this->validate('double', PHP_INT_MAX));
        $this->assertNotNull($this->validate('double', '5'));
        $this->assertNull($this->validate('double', 3.14));
        $this->assertNull($this->validate('double', 7.64E+5));
        $this->assertNull($this->validate('double', INF));
        $this->assertNull($this->validate('double', NAN));
        $this->assertNotNull($this->validate('double', 'text'));
        $this->assertNotNull($this->validate('double', [1, 2, 3]));
        $this->assertNotNull($this->validate('double', ['foo' => 'bar']));
    }

    public function testNumberNode(): void {
        $this->assertNotNull($this->validate('number', null));
        $this->assertNotNull($this->validate('number', true));
        $this->assertNull($this->validate('number', PHP_INT_MIN));
        $this->assertNull($this->validate('number', -100));
        $this->assertNull($this->validate('number', 0));
        $this->assertNull($this->validate('number', 2));
        $this->assertNull($this->validate('number', PHP_INT_MAX));
        $this->assertNull($this->validate('number', '5')); // wow!
        $this->assertNull($this->validate('number', 3.14));
        $this->assertNull($this->validate('number', 7.64E+5));
        $this->assertNull($this->validate('number', INF));
        $this->assertNull($this->validate('number', NAN));
        $this->assertNotNull($this->validate('number', 'text'));
        $this->assertNotNull($this->validate('number', [1, 2, 3]));
        $this->assertNotNull($this->validate('number', ['foo' => 'bar']));
    }

    public function testIntRangeNode(): void {
        $this->assertNotNull($this->validate('int<-1, 1>', null));
        $this->assertNotNull($this->validate('int<-1, 1>', true));
        $this->assertNotNull($this->validate('int<-1, 1>', PHP_INT_MIN));
        $this->assertNotNull($this->validate('int<-1, 1>', -100));
        $this->assertNotNull($this->validate('int<-1, 1>', -2));
        $this->assertNull($this->validate('int<-1, 1>', -1));
        $this->assertNull($this->validate('int<-1, 1>', 0));
        $this->assertNull($this->validate('int<-1, 1>', 1));
        $this->assertNotNull($this->validate('int<-1, 1>', 2));
        $this->assertNotNull($this->validate('int<-1, 1>', PHP_INT_MAX));
        $this->assertNotNull($this->validate('int<-1, 1>', '5'));
        $this->assertNotNull($this->validate('int<-1, 1>', 3.14));
        $this->assertNotNull($this->validate('int<-1, 1>', 7.64E+5));
        $this->assertNotNull($this->validate('int<-1, 1>', INF));
        $this->assertNotNull($this->validate('int<-1, 1>', NAN));
        $this->assertNotNull($this->validate('int<-1, 1>', 'text'));
        $this->assertNotNull($this->validate('int<-1, 1>', [1, 2, 3]));
        $this->assertNotNull($this->validate('int<-1, 1>', ['foo' => 'bar']));
    }

    public function testIntRangeMinNode(): void {
        $this->assertNotNull($this->validate('int<min, 1>', null));
        $this->assertNotNull($this->validate('int<min, 1>', true));
        $this->assertNull($this->validate('int<min, 1>', PHP_INT_MIN));
        $this->assertNull($this->validate('int<min, 1>', -100));
        $this->assertNull($this->validate('int<min, 1>', -2));
        $this->assertNull($this->validate('int<min, 1>', -1));
        $this->assertNull($this->validate('int<min, 1>', 0));
        $this->assertNull($this->validate('int<min, 1>', 1));
        $this->assertNotNull($this->validate('int<min, 1>', 2));
        $this->assertNotNull($this->validate('int<min, 1>', PHP_INT_MAX));
        $this->assertNotNull($this->validate('int<min, 1>', '5'));
        $this->assertNotNull($this->validate('int<min, 1>', 3.14));
        $this->assertNotNull($this->validate('int<min, 1>', 7.64E+5));
        $this->assertNotNull($this->validate('int<min, 1>', INF));
        $this->assertNotNull($this->validate('int<min, 1>', NAN));
        $this->assertNotNull($this->validate('int<min, 1>', 'text'));
        $this->assertNotNull($this->validate('int<min, 1>', [1, 2, 3]));
        $this->assertNotNull($this->validate('int<min, 1>', ['foo' => 'bar']));
    }

    public function testIntRangeMaxNode(): void {
        $this->assertNotNull($this->validate('int<-1, max>', null));
        $this->assertNotNull($this->validate('int<-1, max>', true));
        $this->assertNotNull($this->validate('int<-1, max>', PHP_INT_MIN));
        $this->assertNotNull($this->validate('int<-1, max>', -100));
        $this->assertNotNull($this->validate('int<-1, max>', -2));
        $this->assertNull($this->validate('int<-1, max>', -1));
        $this->assertNull($this->validate('int<-1, max>', 0));
        $this->assertNull($this->validate('int<-1, max>', 1));
        $this->assertNull($this->validate('int<-1, max>', 2));
        $this->assertNull($this->validate('int<-1, max>', PHP_INT_MAX));
        $this->assertNotNull($this->validate('int<-1, max>', '5'));
        $this->assertNotNull($this->validate('int<-1, max>', 3.14));
        $this->assertNotNull($this->validate('int<-1, max>', 7.64E+5));
        $this->assertNotNull($this->validate('int<-1, max>', INF));
        $this->assertNotNull($this->validate('int<-1, max>', NAN));
        $this->assertNotNull($this->validate('int<-1, max>', 'text'));
        $this->assertNotNull($this->validate('int<-1, max>', [1, 2, 3]));
        $this->assertNotNull($this->validate('int<-1, max>', ['foo' => 'bar']));
    }

    public function testIntRangeInvalidNode(): void {
        try {
            $this->validate('int<1>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'int<1> (GenericTypeNode) must have two generic types',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<1, 2, 3>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'int<1, 2, 3> (GenericTypeNode) must have two generic types',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<null, 3>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unsupported lower IdentifierTypeNode null (IdentifierTypeNode)',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<-3, string>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unsupported upper IdentifierTypeNode string (IdentifierTypeNode)',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<null, null>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unsupported lower IdentifierTypeNode null (IdentifierTypeNode)',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<array<int>, 0>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unsupported lower type array<int> (GenericTypeNode)',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<0, array<string>>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unsupported upper type array<string> (GenericTypeNode)',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<"foo", 0>', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unsupported lower constExpr "foo" (ConstExprStringNode)',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('int<0, "bar">', 0);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unsupported upper constExpr "bar" (ConstExprStringNode)',
                $th->getMessage(),
            );
        }
    }

    public function testUnsupportedNumberNodes(): void {
        try {
            $this->validate('scalar', 1);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: scalar',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('3.1', [1, 'foo']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown ConstTypeNode->constExpr 3.1 (ConstExprFloatNode)',
                $th->getMessage(),
            );
        }
    }

    public function testStringNode(): void {
        $this->assertNotNull($this->validate('string', null));
        $this->assertNotNull($this->validate('string', true));
        $this->assertNotNull($this->validate('string', false));
        $this->assertNotNull($this->validate('string', 2));
        $this->assertNull($this->validate('string', 'Foo Bar'));
        $this->assertNull($this->validate('string', '5'));
        $this->assertNull($this->validate('string', '-3.14'));
        $this->assertNull($this->validate('string', ''));
        $this->assertNull($this->validate('string', 'lower'));
        $this->assertNull($this->validate('string', 'UPPER'));
        $this->assertNotNull($this->validate('string', [1, 2, 3]));
        $this->assertNotNull($this->validate('string', ['foo' => 'bar']));
    }

    public function testNumericStringNode(): void {
        $this->assertNotNull($this->validate('numeric-string', null));
        $this->assertNotNull($this->validate('numeric-string', true));
        $this->assertNotNull($this->validate('numeric-string', false));
        $this->assertNotNull($this->validate('numeric-string', 2));
        $this->assertNotNull($this->validate('numeric-string', 'Foo Bar'));
        $this->assertNull($this->validate('numeric-string', '5'));
        $this->assertNull($this->validate('numeric-string', '-3.14'));
        $this->assertNotNull($this->validate('numeric-string', ''));
        $this->assertNotNull($this->validate('numeric-string', 'lower'));
        $this->assertNotNull($this->validate('numeric-string', 'UPPER'));
        $this->assertNotNull($this->validate('numeric-string', [1, 2, 3]));
        $this->assertNotNull($this->validate('numeric-string', ['foo' => 'bar']));
    }

    public function testNonEmptyStringNode(): void {
        $this->assertNotNull($this->validate('non-empty-string', null));
        $this->assertNotNull($this->validate('non-empty-string', true));
        $this->assertNotNull($this->validate('non-empty-string', false));
        $this->assertNotNull($this->validate('non-empty-string', 2));
        $this->assertNull($this->validate('non-empty-string', 'Foo Bar'));
        $this->assertNull($this->validate('non-empty-string', '5'));
        $this->assertNull($this->validate('non-empty-string', '-3.14'));
        $this->assertNotNull($this->validate('non-empty-string', ''));
        $this->assertNull($this->validate('non-empty-string', 'lower'));
        $this->assertNull($this->validate('non-empty-string', 'UPPER'));
        $this->assertNotNull($this->validate('non-empty-string', [1, 2, 3]));
        $this->assertNotNull($this->validate('non-empty-string', ['foo' => 'bar']));
    }

    public function testLowercaseStringNode(): void {
        $this->assertNotNull($this->validate('lowercase-string', null));
        $this->assertNotNull($this->validate('lowercase-string', true));
        $this->assertNotNull($this->validate('lowercase-string', false));
        $this->assertNotNull($this->validate('lowercase-string', 2));
        $this->assertNotNull($this->validate('lowercase-string', 'Foo Bar'));
        $this->assertNotNull($this->validate('lowercase-string', '5'));
        $this->assertNotNull($this->validate('lowercase-string', '-3.14'));
        $this->assertNotNull($this->validate('lowercase-string', ''));
        $this->assertNull($this->validate('lowercase-string', 'lower'));
        $this->assertNotNull($this->validate('lowercase-string', 'UPPER'));
        $this->assertNotNull($this->validate('lowercase-string', [1, 2, 3]));
        $this->assertNotNull($this->validate('lowercase-string', ['foo' => 'bar']));
    }

    public function testConstStringNode(): void {
        $this->assertNotNull($this->validate('"foo"', null));
        $this->assertNotNull($this->validate('"foo"', true));
        $this->assertNotNull($this->validate('"foo"', false));
        $this->assertNotNull($this->validate('"foo"', 2));
        $this->assertNull($this->validate('"foo"', 'foo'));
        $this->assertNotNull($this->validate('"foo"', 'Foo'));
        $this->assertNotNull($this->validate('"foo"', '5'));
        $this->assertNotNull($this->validate('"foo"', '-3.14'));
        $this->assertNotNull($this->validate('"foo"', ''));
        $this->assertNotNull($this->validate('"foo"', 'lower'));
        $this->assertNotNull($this->validate('"foo"', 'UPPER'));
        $this->assertNotNull($this->validate('"foo"', [1, 2, 3]));
        $this->assertNotNull($this->validate('"foo"', ['foo' => 'bar']));
    }

    public function testUnsupportedStringNodes(): void {
        try {
            $this->validate('class-string', 'foo');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: class-string',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('class-string<T>', 'foo');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown GenericTypeNode class-string<T>',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('callable-string', 'foo');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: callable-string',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('non-falsy-string', 'foo');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: non-falsy-string',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('truthy-string', 'foo');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: truthy-string',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('literal-string', 'foo');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: literal-string',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('array-key', 'foo');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: array-key',
                $th->getMessage(),
            );
        }
    }

    public function testUnionNode(): void {
        $this->assertNotNull($this->validate("'foo'|'bar'", null));
        $this->assertNotNull($this->validate("'foo'|'bar'", true));
        $this->assertNotNull($this->validate("'foo'|'bar'", 2));
        $this->assertNotNull($this->validate("'foo'|'bar'", 'text'));
        $this->assertNull($this->validate("'foo'|'bar'", 'foo'));
        $this->assertNull($this->validate("'foo'|'bar'", 'bar'));
        $this->assertNotNull($this->validate("'foo'|'bar'", ''));
        $this->assertNotNull($this->validate("'foo'|'bar'", [1, 2, 3]));
        $this->assertNotNull($this->validate("'foo'|'bar'", ['foo' => 'bar']));

        $this->assertNotNull($this->validate('"foo"|"bar"', null));
        $this->assertNotNull($this->validate('"foo"|"bar"', true));
        $this->assertNotNull($this->validate('"foo"|"bar"', 2));
        $this->assertNotNull($this->validate('"foo"|"bar"', 'text'));
        $this->assertNull($this->validate('"foo"|"bar"', 'foo'));
        $this->assertNull($this->validate('"foo"|"bar"', 'bar'));
        $this->assertNotNull($this->validate('"foo"|"bar"', ''));
        $this->assertNotNull($this->validate('"foo"|"bar"', [1, 2, 3]));
        $this->assertNotNull($this->validate('"foo"|"bar"', ['foo' => 'bar']));

        $this->assertNull($this->validate("null|'foo'|'bar'", null));
        $this->assertNotNull($this->validate("null|'foo'|'bar'", 'text'));
        $this->assertNull($this->validate("null|'foo'|'bar'", 'foo'));
        $this->assertNull($this->validate("null|'foo'|'bar'", 'bar'));
        $this->assertNotNull($this->validate("null|'foo'|'bar'", ''));
    }

    public function testNullableNode(): void {
        $this->assertNull($this->validate("?('foo'|'bar')", null));
        $this->assertNotNull($this->validate("?('foo'|'bar')", 'text'));
        $this->assertNull($this->validate("?('foo'|'bar')", 'foo'));
        $this->assertNull($this->validate("?('foo'|'bar')", 'bar'));
        $this->assertNotNull($this->validate("?('foo'|'bar')", ''));
    }

    public function testBoolArrayNode(): void {
        $this->assertNotNull($this->validate('array<bool>', null));
        $this->assertNotNull($this->validate('array<bool>', true));
        $this->assertNotNull($this->validate('array<bool>', false));
        $this->assertNotNull($this->validate('array<bool>', 2));
        $this->assertNotNull($this->validate('array<bool>', 'text'));
        $this->assertNull($this->validate('array<bool>', []));
        $this->assertNull($this->validate('array<bool>', [false]));
        $this->assertNull($this->validate('array<bool>', [true, false, true]));
        $this->assertNotNull($this->validate('array<bool>', [true, false, null]));
        $this->assertNotNull($this->validate('array<bool>', [1, 2, 3]));
        $this->assertNotNull($this->validate('array<bool>', ['foo' => 'bar']));
    }

    public function testNullableIntArrayNode(): void {
        $this->assertNotNull($this->validate('array<?int>', null));
        $this->assertNotNull($this->validate('array<?int>', true));
        $this->assertNotNull($this->validate('array<?int>', false));
        $this->assertNotNull($this->validate('array<?int>', 2));
        $this->assertNotNull($this->validate('array<?int>', 'text'));
        $this->assertNull($this->validate('array<?int>', []));
        $this->assertNull($this->validate('array<?int>', [1]));
        $this->assertNull($this->validate('array<?int>', [null]));
        $this->assertNull($this->validate('array<?int>', [1, null, -3]));
        $this->assertNotNull($this->validate('array<?int>', [1, 2, 'c']));
        $this->assertNotNull($this->validate('array<?int>', ['a', 'b', 'c']));
        $this->assertNotNull($this->validate('array<?int>', ['foo' => 'bar']));
    }

    public function testStringArrayNode(): void {
        $this->assertNotNull($this->validate('array<string>', null));
        $this->assertNotNull($this->validate('array<string>', true));
        $this->assertNotNull($this->validate('array<string>', false));
        $this->assertNotNull($this->validate('array<string>', 2));
        $this->assertNotNull($this->validate('array<string>', 'text'));
        $this->assertNull($this->validate('array<string>', []));
        $this->assertNull($this->validate('array<string>', ['foo']));
        $this->assertNull($this->validate('array<string>', ['a', 'b', 'c']));
        $this->assertNotNull($this->validate('array<string>', ['a', 'b', null]));
        $this->assertNotNull($this->validate('array<string>', [1, 2, 3]));
        $this->assertNotNull($this->validate('array<string>', ['foo' => 'bar']));
    }

    public function testStringNonEmptyArrayNode(): void {
        $this->assertNotNull($this->validate('non-empty-array<string>', null));
        $this->assertNotNull($this->validate('non-empty-array<string>', true));
        $this->assertNotNull($this->validate('non-empty-array<string>', false));
        $this->assertNotNull($this->validate('non-empty-array<string>', 2));
        $this->assertNotNull($this->validate('non-empty-array<string>', 'text'));
        $this->assertNotNull($this->validate('non-empty-array<string>', []));
        $this->assertNull($this->validate('non-empty-array<string>', ['foo']));
        $this->assertNull($this->validate('non-empty-array<string>', ['a', 'b', 'c']));
        $this->assertNotNull($this->validate('non-empty-array<string>', ['a', 'b', null]));
        $this->assertNotNull($this->validate('non-empty-array<string>', [1, 2, 3]));
        $this->assertNotNull($this->validate('non-empty-array<string>', ['foo' => 'bar']));
    }

    public function testUnsupportedArrayNodes(): void {
        try {
            $this->validate('array', ['foo']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: array',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('non-empty-array', ['foo']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: non-empty-array',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('array<int, int, int>', []);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'array<int, int, int> (GenericTypeNode) must have one or two generic types',
                $th->getMessage(),
            );
        }
    }

    public function testIntToStringDictNode(): void {
        $this->assertNotNull($this->validate('array<int, string>', null));
        $this->assertNotNull($this->validate('array<int, string>', true));
        $this->assertNotNull($this->validate('array<int, string>', false));
        $this->assertNotNull($this->validate('array<int, string>', 2));
        $this->assertNotNull($this->validate('array<int, string>', 'text'));
        $this->assertNull($this->validate('array<int, string>', []));
        $this->assertNull($this->validate('array<int, string>', ['foo']));
        $this->assertNull($this->validate('array<int, string>', ['a', 'b', 'c']));
        $this->assertNull($this->validate('array<int, string>', [2 => 'foo']));
        $this->assertNull($this->validate('array<int, string>', [2 => 'foo', -7 => 'bar']));
        $this->assertNotNull($this->validate('array<int, string>', [null => 'foo']));
        $this->assertNotNull($this->validate('array<int, string>', [2 => null]));
        $this->assertNotNull($this->validate('array<int, string>', [2 => 'foo', null => 'bar']));
        $this->assertNotNull($this->validate('array<int, string>', [2 => 'foo', -7 => null]));
        $this->assertNotNull($this->validate('array<int, string>', [1, 2, 3]));
        $this->assertNotNull($this->validate('array<int, string>', ['foo' => 'bar']));
    }

    public function testBoolToNullableStringDictNode(): void {
        // Note: Bool is an invalid array key; gets casted to int (0 and 1).
        $this->assertNotNull($this->validate('array<bool, ?string>', null));
        $this->assertNotNull($this->validate('array<bool, ?string>', true));
        $this->assertNotNull($this->validate('array<bool, ?string>', false));
        $this->assertNotNull($this->validate('array<bool, ?string>', 2));
        $this->assertNotNull($this->validate('array<bool, ?string>', 'text'));
        $this->assertNull($this->validate('array<bool, ?string>', []));
        $this->assertNotNull($this->validate('array<bool, ?string>', ['foo']));
        $this->assertNotNull($this->validate('array<bool, ?string>', ['a', 'b', 'c']));
        $this->assertNotNull($this->validate('array<bool, ?string>', [false => 'foo']));
        $this->assertNotNull($this->validate('array<bool, ?string>', [false => 'foo', true => 'bar']));
        $this->assertNotNull($this->validate('array<bool, ?string>', [null => 'foo']));
        $this->assertNotNull($this->validate('array<bool, ?string>', [false => null]));
        $this->assertNotNull($this->validate('array<bool, ?string>', [false => 'foo', null => 'bar']));
        $this->assertNotNull($this->validate('array<bool, ?string>', [false => 'foo', true => null]));
        $this->assertNotNull($this->validate('array<bool, ?string>', [1, 2, 3]));
        $this->assertNotNull($this->validate('array<bool, ?string>', ['foo' => 'bar']));
    }

    public function testNullableStringToBoolDictNode(): void {
        $this->assertNotNull($this->validate('array<?string, bool>', null));
        $this->assertNotNull($this->validate('array<?string, bool>', true));
        $this->assertNotNull($this->validate('array<?string, bool>', false));
        $this->assertNotNull($this->validate('array<?string, bool>', 2));
        $this->assertNotNull($this->validate('array<?string, bool>', 'text'));
        $this->assertNull($this->validate('array<?string, bool>', []));
        $this->assertNotNull($this->validate('array<?string, bool>', ['foo']));
        $this->assertNotNull($this->validate('array<?string, bool>', ['a', 'b', 'c']));
        $this->assertNull($this->validate('array<?string, bool>', ['foo' => false]));
        $this->assertNull($this->validate('array<?string, bool>', ['foo' => false, 'bar' => true]));
        $this->assertNull($this->validate('array<?string, bool>', [null => false]));
        $this->assertNotNull($this->validate('array<?string, bool>', [1 => false]));
        $this->assertNotNull($this->validate('array<?string, bool>', ['foo' => null]));
        $this->assertNull($this->validate('array<?string, bool>', ['foo' => false, null => true]));
        $this->assertNotNull($this->validate('array<?string, bool>', ['foo' => false, 1 => true]));
        $this->assertNotNull($this->validate('array<?string, bool>', ['foo' => false, 'bar' => null]));
        $this->assertNotNull($this->validate('array<?string, bool>', [1, 2, 3]));
        $this->assertNotNull($this->validate('array<?string, bool>', ['foo' => 'bar']));
    }

    public function testNullableFloatToStringNonEmptyDictNode(): void {
        $this->assertNotNull($this->validate('non-empty-array<int, string>', null));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', true));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', false));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', 2));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', 'text'));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', []));
        $this->assertNull($this->validate('non-empty-array<int, string>', ['foo']));
        $this->assertNull($this->validate('non-empty-array<int, string>', ['a', 'b', 'c']));
        $this->assertNull($this->validate('non-empty-array<int, string>', [2 => 'foo']));
        $this->assertNull($this->validate('non-empty-array<int, string>', [2 => 'foo', -7 => 'bar']));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', [null => 'foo']));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', [2 => null]));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', [2 => 'foo', null => 'bar']));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', [2 => 'foo', -7 => null]));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', [1, 2, 3]));
        $this->assertNotNull($this->validate('non-empty-array<int, string>', ['foo' => 'bar']));
    }

    public function testObjectNode(): void {
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", null));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", true));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", false));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", 2));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", 'text'));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", []));
        $this->assertNull($this->validate("array{'foo': int, \"bar\": string}", ['foo' => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", ['foo' => 3]));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", [3 => 'foo', 'test' => 'bar']));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", [null => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", ['foo' => null, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", ['foo' => 3, null => 'test']));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", ['foo' => 3, 'bar' => null]));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", [1, 2, 3]));
        $this->assertNotNull($this->validate("array{'foo': int, \"bar\": string}", ['foo' => 'bar']));
    }

    public function testOptionalKeyObjectNode(): void {
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", null));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", true));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", false));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", 2));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", 'text'));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", []));
        $this->assertNull($this->validate("array{foo: int, 'bar'?: string}", ['foo' => 3, 'bar' => 'test']));
        $this->assertNull($this->validate("array{foo: int, 'bar'?: string}", ['foo' => 3]));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", [3 => 'foo', 'test' => 'bar']));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", [null => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", ['foo' => null, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", ['foo' => 3, null => 'test']));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", ['foo' => 3, 'bar' => null]));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", [1, 2, 3]));
        $this->assertNotNull($this->validate("array{foo: int, 'bar'?: string}", ['foo' => 'bar']));
    }

    public function testTupleNode(): void {
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", null));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", true));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", false));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", 2));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", 'text'));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", []));
        $this->assertNull($this->validate("array{0: int, 1?: int}", [1]));
        $this->assertNull($this->validate("array{0: int, 1?: int}", [1, -2]));
        $this->assertNull($this->validate("array{0: int, 1?: int}", [0 => 0]));
        $this->assertNull($this->validate("array{0: int, 1?: int}", [1 => 0, 0 => -7]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [1 => 0, 0 => -7, 2 => null]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [1 => 0, 0 => -7, 2 => 1]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [0 => -7, 1 => 0, 'foo' => 'bar']));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [null => 0, 0 => -7]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [1 => null, 0 => -7]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [1 => 0, null => -7]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [1 => 0, 0 => null]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [1, 2, 3]));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", ['foo']));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", ['foo', 'bar']));
        $this->assertNotNull($this->validate("array{0: int, 1?: int}", [1 => 0]));
    }

    public function testEmptyObjectNode(): void {
        $this->assertNotNull($this->validate("array{}", null));
        $this->assertNotNull($this->validate("array{}", true));
        $this->assertNotNull($this->validate("array{}", false));
        $this->assertNotNull($this->validate("array{}", 2));
        $this->assertNotNull($this->validate("array{}", 'text'));
        $this->assertNull($this->validate("array{}", []));
        $this->assertNotNull($this->validate("array{}", ['foo' => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{}", ['foo' => 3]));
        $this->assertNotNull($this->validate("array{}", [3 => 'foo', 'test' => 'bar']));
        $this->assertNotNull($this->validate("array{}", [null => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{}", ['foo' => null, 'bar' => 'test']));
        $this->assertNotNull($this->validate("array{}", ['foo' => 3, null => 'test']));
        $this->assertNotNull($this->validate("array{}", ['foo' => 3, 'bar' => null]));
        $this->assertNotNull($this->validate("array{}", [1, 2, 3]));
        $this->assertNotNull($this->validate("array{}", ['foo' => 'bar']));
    }

    public function testActualObjectNode(): void {
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", null));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", true));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", false));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", 2));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", 'text'));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", []));
        $this->assertNull($this->validate("object{'foo': int, 'bar'?: string}", ['foo' => 3, 'bar' => 'test']));
        $this->assertNull($this->validate("object{'foo': int, 'bar'?: string}", ['foo' => 3]));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", [3 => 'foo', 'test' => 'bar']));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", [null => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", ['foo' => null, 'bar' => 'test']));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", ['foo' => 3, null => 'test']));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", ['foo' => 3, 'bar' => null]));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", [1, 2, 3]));
        $this->assertNotNull($this->validate("object{'foo': int, 'bar'?: string}", ['foo' => 'bar']));
    }

    public function testEmptyActualObjectNode(): void {
        $this->assertNotNull($this->validate("object{}", null));
        $this->assertNotNull($this->validate("object{}", true));
        $this->assertNotNull($this->validate("object{}", false));
        $this->assertNotNull($this->validate("object{}", 2));
        $this->assertNotNull($this->validate("object{}", 'text'));
        $this->assertNull($this->validate("object{}", []));
        $this->assertNotNull($this->validate("object{}", ['foo' => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("object{}", ['foo' => 3]));
        $this->assertNotNull($this->validate("object{}", [3 => 'foo', 'test' => 'bar']));
        $this->assertNotNull($this->validate("object{}", [null => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("object{}", ['foo' => null, 'bar' => 'test']));
        $this->assertNotNull($this->validate("object{}", ['foo' => 3, null => 'test']));
        $this->assertNotNull($this->validate("object{}", ['foo' => 3, 'bar' => null]));
        $this->assertNotNull($this->validate("object{}", [1, 2, 3]));
        $this->assertNotNull($this->validate("object{}", ['foo' => 'bar']));
    }

    public function testUnsupportedObjectNodes(): void {
        try {
            $this->validate('object', ['foo' => 'bar']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Unknown IdentifierTypeNode name: object',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('array{int, string}', [1, 'foo']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Object key must be ConstExprStringNode, not null',
                $th->getMessage(),
            );
        }
    }

    public function testNamedIntNode(): void {
        $this->assertNotNull($this->validate('NamedInt', null));
        $this->assertNotNull($this->validate('NamedInt', true));
        $this->assertNull($this->validate('NamedInt', PHP_INT_MIN));
        $this->assertNull($this->validate('NamedInt', -100));
        $this->assertNull($this->validate('NamedInt', 0));
        $this->assertNull($this->validate('NamedInt', 2));
        $this->assertNull($this->validate('NamedInt', PHP_INT_MAX));
        $this->assertNotNull($this->validate('NamedInt', '5'));
        $this->assertNotNull($this->validate('NamedInt', 3.14));
        $this->assertNotNull($this->validate('NamedInt', 7.64E+5));
        $this->assertNotNull($this->validate('NamedInt', INF));
        $this->assertNotNull($this->validate('NamedInt', NAN));
        $this->assertNotNull($this->validate('NamedInt', 'text'));
        $this->assertNotNull($this->validate('NamedInt', [1, 2, 3]));
        $this->assertNotNull($this->validate('NamedInt', ['foo' => 'bar']));
    }

    public function testNamedObjectNode(): void {
        $this->assertNotNull($this->validate("NamedObject", null));
        $this->assertNotNull($this->validate("NamedObject", true));
        $this->assertNotNull($this->validate("NamedObject", false));
        $this->assertNotNull($this->validate("NamedObject", 2));
        $this->assertNotNull($this->validate("NamedObject", 'text'));
        $this->assertNotNull($this->validate("NamedObject", []));
        $this->assertNull($this->validate("NamedObject", ['foo' => 3, 'bar' => 'test']));
        $this->assertNull($this->validate("NamedObject", ['foo' => 3]));
        $this->assertNotNull($this->validate("NamedObject", [3 => 'foo', 'test' => 'bar']));
        $this->assertNotNull($this->validate("NamedObject", [null => 3, 'bar' => 'test']));
        $this->assertNotNull($this->validate("NamedObject", ['foo' => null, 'bar' => 'test']));
        $this->assertNotNull($this->validate("NamedObject", ['foo' => 3, null => 'test']));
        $this->assertNotNull($this->validate("NamedObject", ['foo' => 3, 'bar' => null]));
        $this->assertNotNull($this->validate("NamedObject", [1, 2, 3]));
        $this->assertNotNull($this->validate("NamedObject", ['foo' => 'bar']));
    }

    public function testUnsupportedNamedTypeNode(): void {
        try {
            $this->validate('Invalid', null);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Class "PhpTypeScriptApi\Tests\UnitTests\PhpStan\Invalid" does not exist',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('AllegedlyNamedSomething', null);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Only classes extending NamedType may be used.',
                $th->getMessage(),
            );
        }
        try {
            $this->validate('AllegedlyNamedAnotherThing', null);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'Only classes extending NamedType may be used.',
                $th->getMessage(),
            );
        }
    }

    public function testUnsupportedNodes(): void {
        try {
            $this->validate(new ThisTypeNode(), null);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(
                'enterNode: Unknown node class: $this (ThisTypeNode)',
                $th->getMessage(),
            );
        }
    }

    private function validate(string|TypeNode $type, mixed $value): ?string {
        if ($type instanceof TypeNode) {
            $type_node = $type;
        } else {
            $phpStanUtils = new PhpStanUtils();
            $phpDocNode = $phpStanUtils->parseDocComment("/** @return {$type} */");
            $paramTags = $phpDocNode->getReturnTagValues();
            $type_node = $paramTags[0]->type;
        }

        $result_node = ValidateVisitor::validate(__NAMESPACE__, $value, $type_node);
        return $result_node->isValid() ? null : "{$result_node}";
    }
}
