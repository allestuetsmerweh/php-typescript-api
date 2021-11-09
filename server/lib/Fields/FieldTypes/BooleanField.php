<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

require_once __DIR__.'/../../__.php';

class BooleanField extends Field {
    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_bool($value)) {
                $validation_result->recordError(__('fields.must_be_boolean'));
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
                throw new \Exception(__('fields.illegible_boolean', ['value' => $string]));
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
