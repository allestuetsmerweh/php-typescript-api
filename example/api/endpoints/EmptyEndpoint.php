<?php

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;

class EmptyEndpoint extends Endpoint {
    public function runtimeSetup() {
        // no runtime setup required.
    }

    public static function getIdent() {
        return 'EmptyEndpoint';
    }

    public function getResponseField() {
        return new FieldTypes\ObjectField([
            'field_structure' => [],
        ]);
    }

    public function getRequestField() {
        return new FieldTypes\ObjectField([
            'field_structure' => [],
        ]);
    }

    protected function handle($input) {
    }
}
