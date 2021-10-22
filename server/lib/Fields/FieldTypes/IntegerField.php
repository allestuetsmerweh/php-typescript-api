<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

require_once __DIR__.'/NumberField.php';

class IntegerField extends NumberField {
    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_int($value)) {
                $validation_result->recordError("Wert muss eine Ganzzahl sein.");
            }
        }
        return $validation_result;
    }

    public function parse($string) {
        if ($string == '') {
            return null;
        }
        if (preg_match('/^[0-9\\-]+$/', $string)) {
            return intval($string);
        }
        throw new \Exception("Unlesbare Ganzzahl: '{$string}'");
    }
}
