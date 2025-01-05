<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @template Data
 */
interface ApiObjectInterface {
    /** @return Data */
    public function data(): mixed;

    /**
     * @return ApiObjectInterface<Data>
     */
    public static function fromData(mixed $data): ApiObjectInterface;
}
