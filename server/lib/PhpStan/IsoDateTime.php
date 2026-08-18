<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @implements ApiObjectInterface<non-empty-string>
 */
class IsoDateTime extends \DateTime implements ApiObjectInterface {
    public function toWire(): mixed {
        return $this->format('Y-m-d H:i:s');
    }

    public static function fromWire(mixed $data): IsoDateTime {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("IsoDateTime must be string");
        }
        $date_time = \DateTime::createFromFormat('Y-m-d H:i:s', $data);
        if ($date_time === false || $date_time->format('Y-m-d H:i:s') !== $data) {
            throw new \InvalidArgumentException("IsoDateTime must be valid Y-m-d H:i:s format, got: {$data}");
        }
        return new IsoDateTime($date_time->format('Y-m-d H:i:s'));
    }

    public static function fromDateTime(?\DateTimeInterface $date_time): ?IsoDateTime {
        if ($date_time === null) {
            return null;
        }
        return new IsoDateTime($date_time->format('Y-m-d H:i:s'));
    }

    public function __toString(): string {
        return "{$this->toWire()}";
    }
}
