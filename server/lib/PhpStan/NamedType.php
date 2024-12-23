<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @template T
 */
class NamedType {
    /** @param T $data */
    public function __construct(public mixed $data) {
        throw new \Exception("`NamedType`s shall not actually be instantiated");
    }
}
