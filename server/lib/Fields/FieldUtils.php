<?php

namespace PhpTypeScriptApi\Fields;

use PhpTypeScriptApi\Fields\FieldTypes\Field;

class FieldUtils {
    /**
     * @param array<string, mixed> $options
     */
    public function validate(
        Field $field,
        mixed $input,
        array $options = []
    ): mixed {
        $validated = [];
        $errors = [];
        $value = $input ?? null;
        if ($options['parse'] ?? false) {
            try {
                $value = $field->parse($value);
            } catch (\Exception $exc) {
                $errors = ['.' => [$exc->getMessage()]];
                throw new ValidationError($errors);
            }
        }
        $validation_errors = $field->getValidationErrors($value);
        if (empty($validation_errors)) {
            $validated = $value;
        } else {
            $errors = $validation_errors;
        }
        if (!empty($errors)) {
            throw new ValidationError($errors);
        }
        return $validated;
    }

    public static function create(): self {
        return new self();
    }
}
