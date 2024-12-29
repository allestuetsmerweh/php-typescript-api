<?php

use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;

class SquareRootEndpoint extends Endpoint {
    public function runtimeSetup(): void {
        // no runtime setup required.
    }

    public static function getIdent(): string {
        return 'SquareRootEndpoint';
    }

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\NumberField([]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\NumberField(['min_value' => 0.0]);
    }

    protected function handle(mixed $input): mixed {
        return sqrt($input);
    }
}
