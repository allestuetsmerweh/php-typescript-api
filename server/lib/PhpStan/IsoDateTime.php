<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @implements ApiObjectInterface<non-empty-string>
 */
class IsoDateTime extends \DateTime implements ApiObjectInterface {
    public function data(): mixed {
        return $this->format('Y-m-d H:i:s');
    }

    public static function fromData(mixed $data): IsoDateTime {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("IsoDateTime must be string");
        }
        if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $data)) {
            throw new \InvalidArgumentException("IsoDateTime must be Y-m-d H:i:s");
        }
        return new IsoDateTime($data);
    }

    public static function fromDateTime(?\DateTimeInterface $date_time): ?IsoDateTime {
        if ($date_time === null) {
            return null;
        }
        return new IsoDateTime($date_time->format('Y-m-d H:i:s'));
    }

    public function __toString(): string {
        return "{$this->data()}";
    }
}
