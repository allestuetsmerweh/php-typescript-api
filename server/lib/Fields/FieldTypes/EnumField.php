<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

require_once __DIR__.'/../../__.php';

class EnumField extends Field {
    private $allowed_value_map = [];

    public function __construct($config = []) {
        parent::__construct($config);
        $allowed_values = $config['allowed_values'] ?? [];
        $this->allowed_value_map = [];
        foreach ($allowed_values as $allowed_value) {
            $this->allowed_value_map[$allowed_value] = true;
        }
    }

    public function getAllowedValues() {
        return array_keys($this->allowed_value_map);
    }

    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_scalar($value)) {
                $validation_result->recordError(__('fields.must_be_scalar_value'));
            } else {
                $is_allowed_value = $this->allowed_value_map[$value] ?? false;
                if (!$is_allowed_value) {
                    $validation_result->recordError(__('fields.must_be_allowed_value'));
                }
            }
        }
        return $validation_result;
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        $allowed_values = implode('|', array_map(function ($value) {
            return "'{$value}'";
        }, $this->getAllowedValues()));
        return $this->getAllowNull() ? "{$allowed_values}|null" : $allowed_values;
    }
}
