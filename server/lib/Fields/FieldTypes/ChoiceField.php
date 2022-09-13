<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use function PhpTypeScriptApi\__;

class ChoiceField extends Field {
    private $field_map = [];

    public function __construct($config = []) {
        parent::__construct($config);
        $field_map = $config['field_map'] ?? [];
        foreach ($field_map as $key => $field) {
            if (!($field instanceof Field)) {
                throw new \Exception("Field für Schlüssel '{$key}' muss ein Field sein.");
            }
        }
        $this->field_map = $field_map;
    }

    public function getFieldMap() {
        return $this->field_map;
    }

    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_array($value)) {
                $validation_result->recordError(__('fields.must_be_object'));
                return $validation_result;
            }

            $value_keys = array_keys($value);
            if (count($value_keys) != 1) {
                $validation_result->recordError(__('fields.must_have_one_key'));
                return $validation_result;
            }

            $value_key = $value_keys[0];
            $item_field = $this->field_map[$value_key] ?? null;
            if ($item_field === null) {
                $validation_result->recordError(__(
                    'fields.unknown_key', ['key' => $value_key]));
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

    public function parse($string) {
        throw new \Exception("Unlesbares Feld: ChoiceField");
    }

    public function getTypeScriptType($config = []) {
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
        $or_null = $this->getAllowNull() ? '|null' : '';
        return "{$object_types_string}{$or_null}";
    }

    public function getExportedTypeScriptTypes() {
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
