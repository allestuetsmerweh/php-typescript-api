<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

class ObjectField extends Field {
    private $field_structure = [];

    public function __construct($config = []) {
        parent::__construct($config);
        $field_structure = $config['field_structure'] ?? [];
        foreach ($field_structure as $key => $field) {
            if (!($field instanceof Field)) {
                throw new \Exception("`field_structure`['{$key}'] must be an instance of `Field`");
            }
        }
        $this->field_structure = $field_structure;
    }

    public function getFieldStructure() {
        return $this->field_structure;
    }

    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_array($value)) {
                $validation_result->recordError("Wert muss ein Objekt sein.");
                return $validation_result;
            }
            foreach ($this->field_structure as $key => $field) {
                if (array_key_exists($key, $value)) {
                    $item_value = $value[$key];
                    $item_result = $field->validate($item_value);
                    if (!$item_result->isValid()) {
                        $item_errors = $item_result->getErrors();
                        $validation_result->recordErrorInKey($key, $item_errors);
                    }
                } else {
                    $validation_result->recordErrorInKey($key, "Fehlender Schlüssel '{$key}'.");
                }
            }
            foreach ($value as $key => $item_value) {
                if (!isset($this->field_structure[$key])) {
                    $validation_result->recordError("Überflüssiger Schlüssel '{$key}'.");
                }
            }
        }
        return $validation_result;
    }

    public function parse($string) {
        throw new \Exception("Unlesbares Feld: ObjectField");
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        $object_type = "{\n";
        foreach ($this->field_structure as $key => $field) {
            $item_type = $field->getTypeScriptType();
            $object_type .= "    '{$key}': {$item_type},\n";
        }
        $object_type .= "}";
        $or_null = $this->getAllowNull() ? '|null' : '';
        return "{$object_type}{$or_null}";
    }

    public function getExportedTypeScriptTypes() {
        $exported_types = parent::getExportedTypeScriptTypes();
        foreach ($this->field_structure as $key => $field) {
            $exported_types = array_merge(
                $exported_types,
                $field->getExportedTypeScriptTypes()
            );
        }
        return $exported_types;
    }
}
