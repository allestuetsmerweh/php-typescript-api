<?php

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\Fields\ValidationError;

class ZeroDivisionException extends ValidationError {
    public function __construct($field) {
        parent::__construct([$field => ["Cannot divide by zero."]]);
    }
}

class DivideNumbersEndpoint extends Endpoint {
    public function runtimeSetup() {
        // no runtime setup required.
    }

    public static function getIdent() {
        return 'DivideNumbersEndpoint';
    }

    public function getResponseField() {
        return new FieldTypes\NumberField([]);
    }

    public function getRequestField() {
        return new FieldTypes\ObjectField([
            'field_structure' => [
                'dividend' => new FieldTypes\NumberField([]),
                'divisor' => new FieldTypes\NumberField([]),
            ],
        ]);
    }

    protected function handle($input) {
        $dividend = $input['dividend'];
        $divisor = $input['divisor'];
        if ($divisor === 0 || $divisor === 0.0) {
            throw new ZeroDivisionException('divisor');
        }
        return $dividend / $divisor;
    }
}
