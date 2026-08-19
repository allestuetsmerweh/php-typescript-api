<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @implements ApiObjectInterface<non-empty-string>
 */
class IsoDate extends \DateTime implements ApiObjectInterface {
    public function toWire(): mixed {
        return $this->format('Y-m-d');
    }

    public static function fromWire(mixed $data): IsoDate {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("IsoDate must be string");
        }
        $date_time = \DateTime::createFromFormat('Y-m-d', $data);
        if ($date_time === false || $date_time->format('Y-m-d') !== $data) {
            throw new \InvalidArgumentException("IsoDate must be valid Y-m-d format, got: {$data}");
        }
        return new IsoDate($date_time->format('Y-m-d'));
    }

    public static function fromDateTime(?\DateTimeInterface $date_time): ?IsoDate {
        if ($date_time === null) {
            return null;
        }
        return new IsoDate($date_time->format('Y-m-d'));
    }

    public function __toString(): string {
        return "{$this->toWire()}";
    }
}
