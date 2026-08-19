<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\IsoDateTime;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\IsoDateTime
 */
final class IsoDateTimeTest extends UnitTestCase {
    public function testSerializeIsoDateTime(): void {
        $iso_date = new IsoDateTime('2025-01-01 13:27:35');

        $this->assertSame('2025-01-01 13:27:35', $iso_date->toWire());
    }

    public function testDeserializeIsoDateTime(): void {
        $iso_date = IsoDateTime::fromWire('2024-12-31 23:59:59');

        $this->assertSame('2024-12-31 23:59:59', $iso_date->format('Y-m-d H:i:s'));
    }

    public function testDeserializeIllTypedIsoDateTime(): void {
        try {
            IsoDateTime::fromWire(['ill-typed']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('IsoDateTime must be string', $th->getMessage());
        }
    }

    public function testDeserializeMalformedIsoDateTime(): void {
        try {
            IsoDateTime::fromWire('malformed');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('IsoDateTime must be valid Y-m-d H:i:s format, got: malformed', $th->getMessage());
        }
    }

    public function testDeserializeInvalidIsoDateTime(): void {
        try {
            IsoDateTime::fromWire('2024-99-99 99:99:99');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame(\InvalidArgumentException::class, get_class($th));
        }
    }

    public function testFromDateTime(): void {
        $iso_date = IsoDateTime::fromDateTime(new \DateTime('2025-01-01 13:27:35'));

        $this->assertSame('2025-01-01 13:27:35', $iso_date?->format('Y-m-d H:i:s'));
    }

    public function testFromDateTimeNull(): void {
        $this->assertNull(IsoDateTime::fromDateTime(null));
    }

    public function testIsoDateTimeToString(): void {
        $iso_date = new IsoDateTime('2025-01-01 13:27:35');

        $this->assertSame('2025-01-01 13:27:35', "{$iso_date}");
    }
}
