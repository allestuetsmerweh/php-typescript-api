<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

require_once __DIR__.'/Field.php';

abstract class AbstractTemporalField extends Field {
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
            if (!is_string($value)) {
                $validation_result->recordError("Wert muss eine Zeichenkette sein.");
            } else {
                $regex = $this->getRegex();
                if (!preg_match($regex, $value)) {
                    $validation_result->recordError("Wert muss im Format {$regex} sein.");
                }
            }
        }
        if ($this->min_value !== null) {
            if ($value < $this->min_value) {
                $validation_result->recordError("Wert darf nicht kleiner als {$this->min_value} sein.");
            }
        }
        if ($this->max_value !== null) {
            if ($value > $this->max_value) {
                $validation_result->recordError("Wert darf nicht grösser als {$this->max_value} sein.");
            }
        }
        return $validation_result;
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return $this->getAllowNull() ? 'string|null' : 'string';
    }

    abstract protected function getRegex();
}