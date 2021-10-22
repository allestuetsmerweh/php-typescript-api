<?php

class HttpError extends Exception {
    public function __construct($http_status_code, $message, Exception $previous = null) {
        parent::__construct($message, $http_status_code, $previous);
    }

    public function getStructuredAnswer() {
        $is_server_error = floor($this->getCode() / 100) == 5;
        if ($is_server_error) {
            return [
                'message' => $this->getMessage(),
            ];
        }
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
