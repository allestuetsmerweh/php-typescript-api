<?php

use PhpTypeScriptApi\TypedEndpoint;

/**
 * @extends TypedEndpoint<
 *   array{},
 *   array{},
 * >
 */
class EmptyTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        return [];
    }
}
