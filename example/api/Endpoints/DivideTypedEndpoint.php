<?php

use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\TypedEndpoint;

/**
 * @template T of int|float|double|number
 *
 * @phpstan-type DefaultNumberType number
 *
 * @extends TypedEndpoint<
 *   array{dividend: T, divisor: T},
 *   T,
 * >
 */
abstract class DivideTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        $dividend = $input['dividend'];
        $divisor = $input['divisor'];
        if (floatval($divisor) === 0.0) {
            throw new ValidationError(['divisor' => ["Cannot divide by zero."]]);
        }
        // @phpstan-ignore return.type
        return $dividend / $divisor;
    }
}
