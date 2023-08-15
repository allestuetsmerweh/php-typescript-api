<?php

namespace PhpTypeScriptApi;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Api {
    use \Psr\Log\LoggerAwareTrait;

    protected $endpoints = [];

    public function registerEndpoint($name, $endpoint_or_getter) {
        $this->endpoints[$name] = $endpoint_or_getter;
    }

    public function getTypeScriptDefinition($name) {
        $typescript_output = "/** ### This file is auto-generated, modifying is futile! ### */\n\n";
        $typescript_exported_types = [];
        $typescript_endpoint_symbols = '';
        $typescript_request_types = '';
        $typescript_response_types = '';

        $typescript_endpoint_symbols .= "// eslint-disable-next-line no-shadow\n";
        $typescript_endpoint_symbols .= "export type {$name}Endpoint =\n";
        $typescript_request_types .= "export interface {$name}Requests extends {$name}EndpointMapping {\n";
        $typescript_response_types .= "export interface {$name}Responses extends {$name}EndpointMapping {\n";
        foreach ($this->endpoints as $endpoint_name => $endpoint_or_getter) {
            $endpoint = $this->maybeCreateEndpointInstance($endpoint_or_getter);
            $typescript_endpoint_symbols .= "    '{$endpoint_name}'|\n";

            $typescript_request_types .= "    {$endpoint_name}: ";
            $request_field = $endpoint->getRequestField();
            foreach ($request_field->getExportedTypeScriptTypes() as $type_ident => $exported_type) {
                $typescript_exported_types[$type_ident] = "export type {$type_ident} = {$exported_type};\n";
            }
            $request_type = $request_field->getTypeScriptType(['should_substitute' => true]);
            $indented_request_type = str_replace("\n", "\n        ", $request_type);
            $typescript_request_types .= $indented_request_type;
            $typescript_request_types .= ",\n";

            $typescript_response_types .= "    {$endpoint_name}: ";
            $response_field = $endpoint->getResponseField();
            foreach ($response_field->getExportedTypeScriptTypes() as $type_ident => $exported_type) {
                $typescript_exported_types[$type_ident] = "export type {$type_ident} = {$exported_type};\n";
            }
            $response_type = $response_field->getTypeScriptType(['should_substitute' => true]);
            $indented_response_type = str_replace("\n", "\n        ", $response_type);
            $typescript_response_types .= $indented_response_type;
            $typescript_response_types .= ",\n";
        }
        $typescript_endpoint_symbols = substr($typescript_endpoint_symbols, 0, -2);
        $typescript_endpoint_symbols .= ";\n";
        $typescript_request_types .= "}\n";
        $typescript_response_types .= "}\n";

        foreach ($typescript_exported_types as $type_ident => $typescript_exported_type) {
            $typescript_output .= "{$typescript_exported_type}\n";
        }
        $typescript_output .= "{$typescript_endpoint_symbols}\n";
        $typescript_output .= "type {$name}EndpointMapping = {[key in {$name}Endpoint]: unknown};\n\n";
        $typescript_output .= "{$typescript_request_types}\n";
        $typescript_output .= "{$typescript_response_types}\n";
        return $typescript_output;
    }

    public function getEndpointNames() {
        return array_keys($this->endpoints);
    }

    public function getEndpointByName(string $name): Endpoint|null {
        $endpoint_or_getter = $this->endpoints[$name] ?? null;
        if (!$endpoint_or_getter) {
            return null;
        }
        return $this->maybeCreateEndpointInstance($endpoint_or_getter);
    }

    protected function maybeCreateEndpointInstance(
        Endpoint|callable $endpoint_or_getter
    ): Endpoint {
        if (
            is_callable($endpoint_or_getter)
            && !($endpoint_or_getter instanceof Endpoint)
        ) {
            return $endpoint_or_getter();
        }
        return $endpoint_or_getter;
    }

    public function serve() {
        $request = Request::createFromGlobals();
        $response = $this->getResponse($request);
        $response->prepare($request);
        $response->send();
    }

    public function getResponse(Request $request): JsonResponse {
        $translator = Translator::getInstance();
        $translator->setAcceptLangs($request->server->get('HTTP_ACCEPT_LANGUAGE'));
        $endpoint_name = $this->getSanitizedEndpointName($request->server->get('PATH_INFO'));
        try {
            $endpoint_logger = null;
            if ($this->logger) {
                $endpoint_logger = $this->logger->withName("Endpoint:{$endpoint_name}");
                $handler = new \Monolog\ErrorHandler($endpoint_logger);
                $handler->registerErrorHandler();
                $handler->registerExceptionHandler();
            }
            if (!isset($this->endpoints[$endpoint_name])) {
                if ($endpoint_logger) {
                    $endpoint_logger->warning("Invalid endpoint called: {$endpoint_name}");
                }
                throw new HttpError(400, Translator::__('api.invalid_endpoint'));
            }
            $endpoint_or_getter = $this->endpoints[$endpoint_name];
            $endpoint = $this->maybeCreateEndpointInstance($endpoint_or_getter);
            if ($endpoint_logger) {
                $endpoint->setLogger($endpoint_logger);
            } else {
                $endpoint->setLogger(new \Monolog\Logger('NullLogger'));
            }
            $endpoint->setup();
            $input = $endpoint->parseInput($request);
            $result = $endpoint->call($input);
            restore_error_handler();
            restore_exception_handler();
            return new JsonResponse($result, Response::HTTP_OK);
        } catch (HttpError $httperr) {
            return new JsonResponse(
                $httperr->getStructuredAnswer(),
                $httperr->getCode(),
            );
        }
    }

    protected function getSanitizedEndpointName($path_info) {
        $has_path_info = preg_match(
            '/^\/([a-zA-Z0-9]+)$/',
            $path_info,
            $path_info_matches
        );
        if (!$has_path_info) {
            throw new HttpError(400, Translator::__('api.invalid_endpoint'));
        }
        return $path_info_matches[1];
    }
}
