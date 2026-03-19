<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @template Wire
 */
interface ApiObjectInterface {
    /** @return Wire */
    public function toWire(): mixed;

    /**
     * @return ApiObjectInterface<Wire>
     *
     * Note: The param is intentionally untyped, in order to force users to implement validation
     */
    public static function fromWire(mixed $data): ApiObjectInterface;
}
