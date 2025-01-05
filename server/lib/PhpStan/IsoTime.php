<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @implements ApiObjectInterface<non-empty-string>
 */
class IsoTime extends \DateTime implements ApiObjectInterface {
    public function data(): mixed {
        return $this->format('H:i:s');
    }

    public static function fromData(mixed $data): IsoTime {
        if (!is_string($data)) {
            throw new \InvalidArgumentException("IsoTime must be string");
        }
        if (!preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $data)) {
            throw new \InvalidArgumentException("IsoTime must be H:i:s");
        }
        return new IsoTime($data);
    }

    public function __toString(): string {
        return "{$this->data()}";
    }
}
