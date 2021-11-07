<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

class StringField extends Field {
    private $max_length;
    private $allow_empty;

    public function __construct($config = []) {
        parent::__construct($config);
        $this->max_length = $config['max_length'] ?? null;
        $this->allow_empty = $config['allow_empty'] ?? false;
    }

    public function getMaxLength() {
        return $this->max_length;
    }

    public function getAllowEmpty() {
        return $this->allow_empty;
    }

    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_string($value)) {
                $validation_result->recordError("Wert muss eine Zeichenkette sein.");
            }
        }
        if (!$this->allow_empty) {
            if ($value === '') {
                if ($this->getDefaultValue() === null) {
                    $validation_result->recordError("Feld darf nicht leer sein.");
                }
            }
        }
        if ($this->max_length !== null) {
            if (strlen($value) > $this->max_length) {
                $validation_result->recordError("Wert darf maximal {$this->max_length} Zeichen lang sein.");
            }
        }
        return $validation_result;
    }

    public function parse($string) {
        return $string;
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return $this->getAllowNull() ? 'string|null' : 'string';
    }
}
