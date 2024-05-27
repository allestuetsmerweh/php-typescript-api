<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

class ArrayField extends Field {
    private Field $item_field;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = []) {
        parent::__construct($config);
        $item_field = $config['item_field'] ?? null;
        if (!($item_field instanceof Field)) {
            throw new \Exception("`item_field` must be an instance of `Field`");
        }
        $this->item_field = $item_field;
    }

    public function getItemField(): Field {
        return $this->item_field;
    }

    protected function validate(mixed $value): Fields\ValidationResult {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_array($value)) {
                $validation_result->recordError(Translator::__('fields.must_be_array'));
                return $validation_result;
            }
            foreach ($value as $key => $item_value) {
                $item_field = $this->item_field;
                $item_result = $item_field->validate($item_value);
                if (!$item_result->isValid()) {
                    $item_errors = $item_result->getErrors();
                    $validation_result->recordErrorInKey($key, $item_errors);
                }
            }
        }
        return $validation_result;
    }

    public function parse(?string $string): mixed {
        throw new \Exception("Unlesbares Feld: ArrayField");
    }

    /** @param array<string, mixed> $config */
    public function getTypeScriptType(array $config = []): string {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        $item_config = [
            'should_substitute' => $should_substitute,
        ];
        $item_type = $this->item_field->getTypeScriptType($item_config);
        $or_null = $this->getAllowNull() ? '|null' : '';
        return "Array<{$item_type}>{$or_null}";
    }

    /** @return array<string, string> */
    public function getExportedTypeScriptTypes(): array {
        return array_merge(
            parent::getExportedTypeScriptTypes(),
            $this->item_field->getExportedTypeScriptTypes(),
        );
    }
}
