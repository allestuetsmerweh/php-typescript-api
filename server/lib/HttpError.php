<?php

namespace PhpTypeScriptApi;

/**
 * @phpstan-type ErrorsByField array<string, array<string>>
 */
class HttpError extends \Exception {
    /** @param ErrorsByField $errors_by_field */
    public static function validationError(array $errors_by_field): HttpError {
        return new HttpError(
            400,
            Translator::__('endpoint.bad_input'),
            null,
            $errors_by_field,
        );
    }

    /** @param ErrorsByField $errors_by_field */
    public function __construct(
        int $http_status_code,
        string $message,
        ?\Exception $previous = null,
        protected ?array $errors_by_field = null,
    ) {
        parent::__construct($message, $http_status_code, $previous);
    }

    /** @return ErrorsByField */
    public function getErrorsByField(): ?array {
        return $this->errors_by_field;
    }

    /**
     * @return array{message: string, error: ErrorsByField|true}
     */
    public function getStructuredAnswer(): array {
        $error = true;
        $previous_exception = $this->getPrevious();
        if ($previous_exception && method_exists($previous_exception, 'getStructuredAnswer')) {
            $error = $previous_exception->getStructuredAnswer();
        }
        if ($this->errors_by_field !== null) {
            $error = $this->errors_by_field;
        }
        return [
            'status' => $this->getCode(),
            'message' => $this->getMessage(),
            'error' => $error,
        ];
    }
}
