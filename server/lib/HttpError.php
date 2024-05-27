<?php

namespace PhpTypeScriptApi;

class HttpError extends \Exception {
    public function __construct(
        int $http_status_code,
        string $message,
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $http_status_code, $previous);
    }

    /**
     * @return array{message: string, error: array{type: string, validationErrors: array<string, array<mixed>>}}
     */
    public function getStructuredAnswer(): array {
        $structured_previous_error = true;
        $previous_exception = $this->getPrevious();
        if ($previous_exception && method_exists($previous_exception, 'getStructuredAnswer')) {
            $structured_previous_error = $previous_exception->getStructuredAnswer();
        }
        return [
            'message' => $this->getMessage(),
            'error' => $structured_previous_error,
        ];
    }
}
