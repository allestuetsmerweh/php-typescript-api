<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields\Types;

use PhpTypeScriptApi\Fields\FieldTypes;

class FakeItemField extends FieldTypes\Field {
    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if ($value !== 'foo' && $value !== 'bar') {
                $validation_result->recordError("Wert muss 'foo' oder 'bar' sein.");
            }
        }
        return $validation_result;
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return 'ItemType'.($this->getAllowNull() ? '|null' : '');
    }
}
