<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\IsoDate
 */
final class IsoDateTest extends UnitTestCase {
    public function testSerializeIsoDate(): void {
        $iso_date = new IsoDate('2025-01-01');

        $this->assertSame('2025-01-01', $iso_date->data());
    }

    public function testDeserializeIsoDate(): void {
        $iso_date = IsoDate::fromData('2024-12-31');

        $this->assertSame('2024-12-31', $iso_date->format('Y-m-d'));
    }

    public function testDeserializeIllTypedIsoDate(): void {
        try {
            IsoDate::fromData(['ill-typed']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('IsoDate must be string', $th->getMessage());
        }
    }

    public function testDeserializeMalformedIsoDate(): void {
        try {
            IsoDate::fromData('malformed');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('IsoDate must be Y-m-d', $th->getMessage());
        }
    }

    public function testDeserializeInvalidIsoDate(): void {
        try {
            IsoDate::fromData('2024-99-99');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            if (\PHP_VERSION_ID < 80300) {
                $this->assertSame(\Exception::class, get_class($th));
            } else {
                $this->assertSame(\DateMalformedStringException::class, get_class($th));
            }
        }
    }

    public function testIsoDateToString(): void {
        $iso_date = new IsoDate('2025-01-01');

        $this->assertSame('2025-01-01', "{$iso_date}");
    }
}
