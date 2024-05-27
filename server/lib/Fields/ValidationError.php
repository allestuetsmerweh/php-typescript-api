<?php

namespace PhpTypeScriptApi\Fields;

class ValidationError extends \Exception {
    /** @var array<string, array<mixed>> */
    public array $validationErrors;

    /** @param array<string, array<mixed>> $validation_errors */
    public function __construct(
        array $validation_errors,
        ?\Exception $previous = null,
    ) {
        $json_errors = json_encode($validation_errors);
        parent::__construct("Validation Error: {$json_errors}", 0, $previous);
        $this->validationErrors = $validation_errors;
    }

    /** @return array<string, array<mixed>> */
    public function getValidationErrors(): array {
        return $this->validationErrors;
    }

    /** @return array{type: string, validationErrors: array<string, array<mixed>>} */
    public function getStructuredAnswer(): array {
        return [
            'type' => 'ValidationError',
            'validationErrors' => $this->validationErrors,
        ];
    }
}
