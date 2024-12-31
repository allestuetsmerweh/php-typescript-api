<?php

use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\TypedEndpoint;

/**
 * @extends TypedEndpoint<
 *   array{dividend: number, divisor: number},
 *   number,
 * >
 */
class DivideNumbersTypedEndpoint extends TypedEndpoint {
    public function runtimeSetup(): void {
        // no runtime setup required.
    }

    public static function getIdent(): string {
        return 'DivideNumbersTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        $dividend = floatval($input['dividend']);
        $divisor = floatval($input['divisor']);
        if ($divisor === 0.0) {
            throw new ValidationError(['divisor' => ["Cannot divide by zero."]]);
        }
        return $dividend / $divisor;
    }
}
