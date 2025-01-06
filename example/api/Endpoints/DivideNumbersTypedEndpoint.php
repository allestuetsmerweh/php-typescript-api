<?php

use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\TypedEndpoint;

/**
 * @template T of int|float|double|number
 *
 * @extends TypedEndpoint<
 *   array{dividend: T, divisor: T},
 *   T,
 * >
 */
abstract class DivideTypedEndpoint extends TypedEndpoint {
    public static function getApiObjectClasses(): array {
        return [];
    }

    public function runtimeSetup(): void {
        // no runtime setup required.
    }

    public static function getIdent(): string {
        return 'DivideNumbersTypedEndpoint';
    }

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

/**
 * @extends DivideTypedEndpoint<number>
 */
class DivideNumbersTypedEndpoint extends DivideTypedEndpoint {
}
