<?php

class ValidationError extends Exception {
    public $validationErrors;

    public function __construct($validation_errors, Exception $previous = null) {
        $json_errors = json_encode($validation_errors);
        parent::__construct("Validation Error: {$json_errors}", 0, $previous);
        $this->validationErrors = $validation_errors;
    }

    public function getValidationErrors() {
        return $this->validationErrors;
    }

    public function getStructuredAnswer() {
        return [
            'type' => 'ValidationError',
            'validationErrors' => $this->validationErrors,
        ];
    }
}
