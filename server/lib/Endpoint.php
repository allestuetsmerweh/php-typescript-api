<?php

namespace PhpTypeScriptApi;

use PhpTypeScriptApi\Fields\FieldTypes\Field;
use Symfony\Component\HttpFoundation\Request;

abstract class Endpoint implements EndpointInterface {
    use \Psr\Log\LoggerAwareTrait;

    private mixed $setupFunction = null;

    public function setup(): void {
        $setup_function = $this->setupFunction;
        if ($setup_function == null) {
            $this->runtimeSetup();
            return;
        }
        $setup_function($this);
    }

    public function runtimeSetup(): void {
        $this->logger?->critical("Setup function must be set!");
        throw new \Exception("Setup function must be set");
    }

    /** Override to enjoy throttling! */
    public function shouldFailThrottling(): bool {
        return false;
    }

    public function setSetupFunction(?callable $new_setup_function): void {
        $this->setupFunction = $new_setup_function;
    }

    /** Override to handle custom requests. */
    public function parseInput(Request $request): mixed {
        $input = json_decode($request->getContent(), true);
        // GET param `request`.
        if (!$input && $request->query->has('request')) {
            $input = json_decode($request->get('request'), true);
        }
        return $input;
    }

    public function call(mixed $raw_input): mixed {
        if ($this->shouldFailThrottling()) {
            $this->logger?->notice("Throttled user request");
            throw new HttpError(429, Translator::__('endpoint.too_many_requests'));
        }
        $field_utils = Fields\FieldUtils::create();

        try {
            $validated_input = $field_utils->validate($this->getRequestField(), $raw_input);
            $this->logger?->info("Valid user request");
        } catch (Fields\ValidationError $verr) {
            $this->logger?->notice("Bad user request", $verr->getStructuredAnswer());
            throw new HttpError(400, Translator::__('endpoint.bad_input'), $verr);
        }

        try {
            $raw_result = $this->handle($validated_input);
        } catch (Fields\ValidationError $verr) {
            $this->logger?->notice("Bad user request", $verr->getStructuredAnswer());
            throw new HttpError(400, Translator::__('endpoint.bad_input'), $verr);
        } catch (HttpError $http_error) {
            $this->logger?->notice("HTTP error {$http_error->getCode()}", [$http_error]);
            throw $http_error;
        } catch (\Exception $exc) {
            $message = $exc->getMessage();
            $this->logger?->critical("Unexpected endpoint error: {$message}", $exc->getTrace());
            throw new HttpError(500, Translator::__('endpoint.internal_server_error'), $exc);
        }

        try {
            $validated_result = $field_utils->validate($this->getResponseField(), $raw_result);
            $this->logger?->info("Valid user response");
        } catch (Fields\ValidationError $verr) {
            $this->logger?->critical("Bad output prohibited", $verr->getStructuredAnswer());
            throw new HttpError(500, Translator::__('endpoint.internal_server_error'), $verr);
        }

        return $validated_result;
    }

    /** @return array<string, string> */
    public function getNamedTsTypes(): array {
        return [
            ...$this->getRequestField()->getExportedTypeScriptTypes(),
            ...$this->getResponseField()->getExportedTypeScriptTypes(),
        ];
    }

    public function getRequestTsType(): string {
        return $this->getRequestField()->getTypeScriptType(['should_substitute' => true]);
    }

    public function getResponseTsType(): string {
        return $this->getResponseField()->getTypeScriptType(['should_substitute' => true]);
    }

    abstract public function getRequestField(): Field;

    abstract public function getResponseField(): Field;

    abstract protected function handle(mixed $input): mixed;
}
