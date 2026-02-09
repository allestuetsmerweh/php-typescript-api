<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

class EnumField extends Field {
    /** @var array<string, bool> */
    private array $allowed_value_map = [];

    /** @param array<string, mixed> $config */
    public function __construct(array $config = []) {
        parent::__construct($config);
        $allowed_values = $config['allowed_values'] ?? [];
        if (count($allowed_values) <= 0) {
            throw new \Exception('`allowed_values` must not be empty.');
        }
        $this->allowed_value_map = [];
        foreach ($allowed_values as $allowed_value) {
            if (!is_string($allowed_value)) {
                throw new \Exception('`allowed_values` must all be strings.');
            }
            $this->allowed_value_map[$allowed_value] = true;
        }
    }

    /** @return array<string> */
    public function getAllowedValues(): array {
        return array_keys($this->allowed_value_map);
    }

    protected function validate(mixed $value): Fields\ValidationResult {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_scalar($value)) {
                $validation_result->recordError(Translator::__('fields.must_be_scalar_value'));
            } else {
                // @phpstan-ignore-next-line offsetAccess.invalidOffset
                $is_allowed_value = $this->allowed_value_map[$value] ?? false;
                if (!$is_allowed_value) {
                    $validation_result->recordError(Translator::__('fields.must_be_allowed_value'));
                }
            }
        }
        return $validation_result;
    }

    /** @param array<string, mixed> $config */
    public function getTypeScriptType(array $config = []): string {
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
