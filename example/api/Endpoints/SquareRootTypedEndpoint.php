<?php

use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\TypedEndpoint;

/**
 * @extends TypedEndpoint<
 *   float|int<0, max>,
 *   float,
 * >
 */
class SquareRootTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        if ($input < 0.0) {
            throw HttpError::validationError(['.' => ['Value must not be negative']]);
        }
        return sqrt((float) $input);
    }
}
