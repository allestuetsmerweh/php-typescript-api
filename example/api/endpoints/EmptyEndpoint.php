<?php

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;

class EmptyEndpoint extends Endpoint {
    public function runtimeSetup(): void {
        // no runtime setup required.
    }

    public static function getIdent(): string {
        return 'EmptyEndpoint';
    }

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\ObjectField([
            'field_structure' => [],
        ]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\ObjectField([
            'field_structure' => [],
        ]);
    }

    protected function handle(mixed $input): mixed {
        return [];
    }
}
