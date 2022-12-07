<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

class Field {
    private bool $allow_null = false;
    private $default_value;
    protected ?string $export_as = null;

    public function __construct($config = []) {
        $this->allow_null = $config['allow_null'] ?? false;
        $this->default_value = $config['default_value'] ?? null;
        $this->export_as = $config['export_as'] ?? null;
    }

    public function getAllowNull() {
        return $this->allow_null;
    }

    public function getDefaultValue() {
        return $this->default_value;
    }

    public function getValidationErrors($value) {
        $validation_result = $this->validate($value);
        return $validation_result->getErrors();
    }

    protected function validate($value) {
        $validation_result = Fields\ValidationResult::create();
        if (!$this->allow_null) {
            if ($value === null) {
                if ($this->default_value === null) {
                    $validation_result->recordError(Translator::__('fields.must_not_be_empty'));
                }
            }
        }
        return $validation_result;
    }

    public function parse($string) {
        if ($string == '') {
            return null;
        }
        return $string;
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return 'unknown';
    }

    public function getExportedTypeScriptTypes() {
        if ($this->export_as !== null) {
            return [
                $this->export_as => $this->getTypeScriptType([
                    'should_substitute' => false,
                ]),
            ];
        }
        return [];
    }
}
