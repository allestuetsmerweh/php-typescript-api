<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Translator;

class BooleanField extends Field {
    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_bool($value)) {
                $validation_result->recordError(Translator::__('fields.must_be_boolean'));
            }
        }
        return $validation_result;
    }

    public function parse($string) {
        switch ($string) {
            case 'true':
            case '1':
                return true;
            case 'false':
            case '0':
                return false;
            case '':
                return null;
            default:
                throw new \Exception(Translator::__('fields.illegible_boolean', ['value' => $string]));
        }
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return $this->getAllowNull() ? 'boolean|null' : 'boolean';
    }
}
