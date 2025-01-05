<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan;

use PhpTypeScriptApi\PhpStan\IsoTime;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\PhpStan\IsoTime
 */
final class IsoTimeTest extends UnitTestCase {
    public function testSerializeIsoTime(): void {
        $iso_date = new IsoTime('13:27:35');

        $this->assertSame('13:27:35', $iso_date->data());
    }

    public function testDeserializeIsoTime(): void {
        $iso_date = IsoTime::fromData('23:59:59');

        $this->assertSame('23:59:59', $iso_date->format('H:i:s'));
    }

    public function testDeserializeIllTypedIsoTime(): void {
        try {
            IsoTime::fromData(['ill-typed']);
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('IsoTime must be string', $th->getMessage());
        }
    }

    public function testDeserializeMalformedIsoTime(): void {
        try {
            IsoTime::fromData('malformed');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            $this->assertSame('IsoTime must be H:i:s', $th->getMessage());
        }
    }

    public function testDeserializeInvalidIsoTime(): void {
        try {
            IsoTime::fromData('99:99:99');
            $this->fail('Error expected');
        } catch (\Throwable $th) {
            if (\PHP_VERSION_ID < 80300) {
                $this->assertSame(\Exception::class, get_class($th));
            } else {
                $this->assertSame(\DateMalformedStringException::class, get_class($th));
            }
        }
    }

    public function testIsoTimeToString(): void {
        $iso_date = new IsoTime('13:27:35');

        $this->assertSame('13:27:35', "{$iso_date}");
    }
}
