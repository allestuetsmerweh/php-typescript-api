<?php

namespace PhpTypeScriptApi;

require_once __DIR__.'/HttpError.php';
require_once __DIR__.'/__.php';

abstract class Endpoint {
    use \Psr\Log\LoggerAwareTrait;

    private $setupFunction;

    public function setup() {
        $setup_function = $this->setupFunction;
        if ($setup_function == null) {
            $this->runtimeSetup();
            return;
        }
        $setup_function($this);
    }

    public function runtimeSetup() {
        throw new \Exception("Setup function must be set");
    }

    abstract public static function getIdent();

    /** Override to enjoy throttling! */
    public function shouldFailThrottling() {
        return false;
    }

    public function setSetupFunction($new_setup_function) {
        $this->setupFunction = $new_setup_function;
    }

    /** Override to handle custom requests. */
    public function parseInput() {
        global $_GET, $_POST;
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                $this->logger->warning("Providing the value of '{$key}' over POST will be deprecated!");
                $input[$key] = json_decode($value, true);
            }
        }
        if (is_array($_GET)) {
            foreach ($_GET as $key => $value) {
                $this->logger->warning("Providing the value of '{$key}' over GET will be deprecated!");
                $input[$key] = json_decode($value, true);
            }
        }
        return $input;
    }

    public function call($raw_input) {
        if ($this->shouldFailThrottling()) {
            $this->logger->error("Throttled user request");
            throw new HttpError(429, __('endpoint.too_many_requests'));
        }
        $field_utils = Fields\FieldUtils::create();

        try {
            $validated_input = $field_utils->validate($this->getRequestField(), $raw_input);
            // "Valid user request"
            $this->logger->info("Valid user request");
        } catch (Fields\ValidationError $verr) {
            $this->logger->warning("Bad user request", $verr->getStructuredAnswer());
            throw new HttpError(400, __('endpoint.bad_input'), $verr);
        }

        try {
            $raw_result = $this->handle($validated_input);
        } catch (Fields\ValidationError $verr) {
            $this->logger->warning("Bad user request", $verr->getStructuredAnswer());
            throw new HttpError(400, __('endpoint.bad_input'), $verr);
        } catch (HttpError $http_error) {
            throw $http_error;
        } catch (\Exception $exc) {
            $message = $exc->getMessage();
            $this->logger->critical("Unexpected endpoint error: {$message}", $exc->getTrace());
            throw new HttpError(500, __('endpoint.internal_server_error'), $exc);
        }

        try {
            $validated_result = $field_utils->validate($this->getResponseField(), $raw_result);
            $this->logger->info("Valid user response");
        } catch (Fields\ValidationError $verr) {
            $this->logger->critical("Bad output prohibited", $verr->getStructuredAnswer());
            throw new HttpError(500, __('endpoint.internal_server_error'), $verr);
        }

        return $validated_result;
    }

    abstract public function getRequestField();

    abstract public function getResponseField();

    abstract protected function handle($input);
}
