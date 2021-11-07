<?php

namespace PhpTypeScriptApi;

class HttpError extends \Exception {
    public function __construct($http_status_code, $message, \Exception $previous = null) {
        parent::__construct($message, $http_status_code, $previous);
    }

    public function getStructuredAnswer() {
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
