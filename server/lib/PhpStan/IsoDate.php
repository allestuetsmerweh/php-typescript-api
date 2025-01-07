<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @implements ApiObjectInterface<non-empty-string>
 */
class IsoDate extends \DateTime implements ApiObjectInterface {
    public function data(): mixed {
        return $this->format('Y-m-d');
    }

    public static function fromData(mixed $data): IsoDate {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("IsoDate must be string");
        }
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $data)) {
            throw new \InvalidArgumentException("IsoDate must be Y-m-d");
        }
        return new IsoDate($data);
    }

    public static function fromDateTime(?\DateTimeInterface $date_time): ?IsoDate {
        if ($date_time === null) {
            return null;
        }
        return new IsoDate($date_time->format('Y-m-d'));
    }

    public function __toString(): string {
        return "{$this->data()}";
    }
}
