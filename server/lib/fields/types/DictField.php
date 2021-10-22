<?php

require_once __DIR__.'/Field.php';

class DictField extends Field {
    private Field $item_field;

    public function __construct($config = []) {
        parent::__construct($config);
        $item_field = $config['item_field'] ?? [];
        if (!($item_field instanceof Field)) {
            throw new Exception("`item_field` must be an instance of `Field`");
        }
        $this->item_field = $item_field;
    }

    public function getItemField() {
        return $this->item_field;
    }

    protected function validate($value) {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_array($value)) {
                $validation_result->recordError("Wert muss ein Objekt sein.");
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

    public function parse($string) {
        throw new Exception("Unlesbares Feld: DictField");
    }

    public function getTypeScriptType($config = []) {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        $item_type = $this->item_field->getTypeScriptType();
        $or_null = $this->getAllowNull() ? '|null' : '';
        return "{[key: string]: {$item_type}}{$or_null}";
    }

    public function getExportedTypeScriptTypes() {
        return array_merge(
            parent::getExportedTypeScriptTypes(),
            $this->item_field->getExportedTypeScriptTypes(),
        );
    }
}
