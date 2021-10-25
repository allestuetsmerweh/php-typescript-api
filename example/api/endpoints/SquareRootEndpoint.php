<?php

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;

class SquareRootEndpoint extends Endpoint {
    public function runtimeSetup() {
        // no runtime setup required.
    }

    public static function getIdent() {
        return 'SquareRootEndpoint';
    }

    public function getResponseField() {
        return new FieldTypes\NumberField([]);
    }

    public function getRequestField() {
        return new FieldTypes\NumberField(['min_value' => 0.0]);
    }

    protected function handle($input) {
        return sqrt($input);
    }
}
