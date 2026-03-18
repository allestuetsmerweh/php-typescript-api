<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\JsonEncoded;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @extends JsonEncoded<'foo'>
 */
class JsonEncodedFoo extends JsonEncoded {
}

/**
 * @template T
 *
 * @extends JsonEncoded<array<T>>
 */
class JsonEncodedGenericArray extends JsonEncoded {
}

/**
 * @extends JsonEncodedGenericArray<int>
 */
class JsonEncodedIntArray extends JsonEncodedGenericArray {
}

/**
 * @extends JsonEncoded<resource>
 */
class JsonEncodedNonEncodable extends JsonEncoded {
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\JsonEncoded
 */
final class JsonEncodedTest extends UnitTestCase {
    public function testDeserializeJsonEncodedFoo(): void {
        $json_encoded = JsonEncodedFoo::fromWire('"foo"');

        $this->assertSame('"foo"', $json_encoded->toWire());
        $this->assertSame('foo', $json_encoded->toData());
        $this->assertSame('"foo"', "{$json_encoded}");
    }

    public function testDeserializeIllTypedJsonEncodedFoo(): void {
        try {
            JsonEncodedFoo::fromWire(['ill-typed']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('PhpTypeScriptApi\Tests\UnitTests\PhpStan\JsonEncodedFoo must be string', $th->getMessage());
        }
    }

    public function testDeserializeMalformedJsonEncodedFoo(): void {
        try {
            JsonEncodedFoo::fromWire('malformed');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('PhpTypeScriptApi\Tests\UnitTests\PhpStan\JsonEncodedFoo must be valid JSON', $th->getMessage());
        }
    }

    public function testDeserializeInvalidJsonEncodedFoo(): void {
        try {
            JsonEncodedFoo::fromWire('"bar"');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('PhpTypeScriptApi\Tests\UnitTests\PhpStan\JsonEncodedFoo must be valid \'foo\'', $th->getMessage());
        }
    }

    public function testJsonEncodedFooFromData(): void {
        $json_encoded = JsonEncodedFoo::fromData('foo');

        $this->assertSame('"foo"', $json_encoded->toWire());
        $this->assertSame('foo', $json_encoded->toData());
        $this->assertSame('"foo"', "{$json_encoded}");
    }

    // ---

    public function testDeserializeJsonEncodedIntArray(): void {
        $json_encoded = JsonEncodedIntArray::fromWire('[1, 2, 3]');

        $this->assertSame('[1, 2, 3]', $json_encoded->toWire());
        $this->assertSame([1, 2, 3], $json_encoded->toData());
        $this->assertSame('[1, 2, 3]', "{$json_encoded}");
    }

    public function testDeserializeIllTypedJsonEncodedIntArray(): void {
        try {
            JsonEncodedIntArray::fromWire(['ill-typed']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('PhpTypeScriptApi\Tests\UnitTests\PhpStan\JsonEncodedIntArray must be string', $th->getMessage());
        }
    }

    public function testDeserializeMalformedJsonEncodedIntArray(): void {
        try {
            JsonEncodedIntArray::fromWire('malformed');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('PhpTypeScriptApi\Tests\UnitTests\PhpStan\JsonEncodedIntArray must be valid JSON', $th->getMessage());
        }
    }

    public function testDeserializeInvalidJsonEncodedIntArray(): void {
        try {
            JsonEncodedIntArray::fromWire('["not an int"]');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('PhpTypeScriptApi\Tests\UnitTests\PhpStan\JsonEncodedIntArray must be valid array<int>', $th->getMessage());
        }
    }

    public function testJsonEncodedIntArrayFromData(): void {
        $json_encoded = JsonEncodedIntArray::fromData([1, 2, 3]);

        $this->assertSame('[1,2,3]', $json_encoded->toWire());
        $this->assertSame([1, 2, 3], $json_encoded->toData());
        $this->assertSame('[1,2,3]', "{$json_encoded}");
    }

    // ---

    public function testDeserializeJsonEncodedNonEncodable(): void {
        try {
            JsonEncodedNonEncodable::fromWire('{}');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('Unknown IdentifierTypeNode name: resource', $th->getMessage());
        }
    }

    public function testJsonEncodedNonEncodableFromData(): void {
        try {
            $resource = tmpfile();
            JsonEncodedNonEncodable::fromData($resource);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('JsonEncoded::fromData with invalid data: NULL', $th->getMessage());
        }
    }
}
