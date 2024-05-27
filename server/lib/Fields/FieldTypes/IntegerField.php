<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

class IntegerField extends NumberField {
    protected function validate(mixed $value): Fields\ValidationResult {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_int($value)) {
                $validation_result->recordError(Translator::__('fields.must_be_integer'));
            }
        }
        return $validation_result;
    }

    public function parse(?string $string): mixed {
        if ($string === null || $string === '') {
            return null;
        }
        if (preg_match('/^[0-9\\-]+$/', $string)) {
            return intval($string);
        }
        throw new \Exception(Translator::__('fields.illegible_integer', ['value' => $string]));
    }
}
