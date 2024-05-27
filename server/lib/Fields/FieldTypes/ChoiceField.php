<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

class ChoiceField extends Field {
    /** @var array<string, Field> */
    private array $field_map = [];

    /** @param array<string, mixed> $config */
    public function __construct(array $config = []) {
        parent::__construct($config);
        $field_map = $config['field_map'] ?? null;
        if ($field_map === null) {
            throw new \Exception('`field_map` must be defined.');
        }
        foreach ($field_map as $key => $field) {
            if (!($field instanceof Field)) {
                throw new \Exception("Field für Schlüssel '{$key}' muss ein Field sein.");
            }
        }
        $this->field_map = $field_map;
    }

    /** @return array<string, Field> */
    public function getFieldMap(): array {
        return $this->field_map;
    }

    protected function validate(mixed $value): Fields\ValidationResult {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_array($value)) {
                $validation_result->recordError(Translator::__('fields.must_be_object'));
                return $validation_result;
            }

            $value_keys = array_keys($value);
            if (count($value_keys) != 1) {
                $validation_result->recordError(Translator::__('fields.must_have_one_key'));
                return $validation_result;
            }

            $value_key = $value_keys[0];
            $item_field = $this->field_map[$value_key] ?? null;
            if ($item_field === null) {
                $validation_result->recordError(Translator::__(
                    'fields.unknown_key',
                    ['key' => $value_key]
                ));
                return $validation_result;
            }

            $item_value = $value[$value_key] ?? null;
            $item_result = $item_field->validate($item_value);
            if (!$item_result->isValid()) {
                $item_errors = $item_result->getErrors();
                $validation_result->recordErrorInKey($value_key, $item_errors);
            }
        }
        return $validation_result;
    }

    public function parse(?string $string): mixed {
        throw new \Exception("Unlesbares Feld: ChoiceField");
    }

    /** @param array<string, mixed> $config */
    public function getTypeScriptType(array $config = []): string {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        $object_types = [];
        foreach ($this->field_map as $key => $field) {
            $object_type = "{\n";
            $item_type = $field->getTypeScriptType();
            $object_type .= "    '{$key}': {$item_type},\n";
            $object_type .= "}";
            $object_types[] = $object_type;
        }
        $object_types_string = implode('|', $object_types);
        if ($object_types_string === '') {
            $object_types_string = "Record<string, never>";
        }
        $or_null = $this->getAllowNull() ? '|null' : '';
        return "{$object_types_string}{$or_null}";
    }

    /** @return array<string, string> */
    public function getExportedTypeScriptTypes(): array {
        $exported_types = parent::getExportedTypeScriptTypes();
        foreach ($this->field_map as $key => $field) {
            $exported_types = array_merge(
                $exported_types,
                $field->getExportedTypeScriptTypes()
            );
        }
        return $exported_types;
    }
}
