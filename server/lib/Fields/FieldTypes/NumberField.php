<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

require_once __DIR__.'/../../__.php';

class NumberField extends Field {
    private $min_value;
    private $max_value;

    public function __construct($config = []) {
        parent::__construct($config);
        $this->min_value = $config['min_value'] ?? null;
        $this->max_value = $config['max_value'] ?? null;
    }

    public function getMinValue() {
        return $this->min_value;
    }

    public function getMaxValue() {
        return $this->max_value;
    }

    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_numeric($value)) {
                $validation_result->recordError(__('fields.must_be_number'));
            }
            if ($this->min_value !== null) {
                if ($value < $this->min_value) {
                    $validation_result->recordError(__(
                        'fields.must_not_be_smaller',
                        ['min_value' => $this->min_value]
                    ));
                }
            }
            if ($this->max_value !== null) {
                if ($value > $this->max_value) {
                    $validation_result->recordError(__(
                        'fields.must_not_be_larger',
                        ['max_value' => $this->max_value]
                    ));
                }
            }
        }
        return $validation_result;
    }

    public function parse($string) {
        if ($string == '') {
            return null;
        }
        if (preg_match('/^[0-9\\.\\-]+$/', $string)) {
            return floatval($string);
        }
        throw new \Exception(__('fields.illegible_number', ['value' => $string]));
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return $this->getAllowNull() ? 'number|null' : 'number';
    }
}
