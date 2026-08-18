<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @implements ApiObjectInterface<non-empty-string>
 */
class IsoTime extends \DateTime implements ApiObjectInterface {
    public function toWire(): mixed {
        return $this->format('H:i:s');
    }

    public static function fromWire(mixed $data): IsoTime {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("IsoTime must be string");
        }
        $date_time = \DateTime::createFromFormat('H:i:s', $data);
        if ($date_time === false || $date_time->format('H:i:s') !== $data) {
            throw new \InvalidArgumentException("IsoTime must be valid H:i:s format, got: {$data}");
        }
        return new IsoTime($date_time->format('H:i:s'));
    }

    public static function fromDateTime(?\DateTimeInterface $date_time): ?IsoTime {
        if ($date_time === null) {
            return null;
        }
        return new IsoTime($date_time->format('H:i:s'));
    }

    public function __toString(): string {
        return "{$this->toWire()}";
    }
}
