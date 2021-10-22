<?php

require_once __DIR__.'/../../../lib/api/Endpoint.php';
require_once __DIR__.'/../../../lib/fields/types/NumberField.php';
require_once __DIR__.'/../../../lib/fields/types/ObjectField.php';

class ZeroDivisionException extends Exception {
    public function __construct() {
        parent::__construct("Cannot divide by zero.");
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
        return new NumberField([]);
    }

    public function getRequestField() {
        return new ObjectField([
            'field_structure' => [
                'dividend' => new NumberField([]),
                'divisor' => new NumberField([]),
            ],
        ]);
    }

    protected function handle($input) {
        $dividend = $input['dividend'];
        $divisor = $input['divisor'];
        if ($divisor === 0 || $divisor === 0.0) {
            throw new ZeroDivisionException();
        }
        return $dividend / $divisor;
    }
}
