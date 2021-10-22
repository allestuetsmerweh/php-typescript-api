<?php

class ValidationResult {
    public $errors = [];

    public function recordError($message) {
        $this->recordErrorInKey('.', $message);
    }

    public function recordErrorInKey($key, $message) {
        $errors = $this->errors[$key] ?? [];
        $errors[] = $message;
        $this->errors[$key] = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function isValid() {
        return empty($this->errors);
    }

    public static function create() {
        return new self();
    }
}
