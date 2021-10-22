<?php

namespace PhpTypeScriptApi;

require_once __DIR__.'/HttpError.php';

class Api {
    use \Psr\Log\LoggerAwareTrait;

    protected $endpoints = [];

    public function registerEndpoint($name, $get_instance) {
        $this->endpoints[$name] = $get_instance;
    }

    public function getTypeScriptDefinition($name) {
        $typescript_output = "/** ### This file is auto-generated, modifying is futile! ### */\n\n";
        $typescript_exported_types = '';
        $typescript_endpoint_enum = '';
        $typescript_request_types = '';
        $typescript_response_types = '';

        $typescript_endpoint_enum .= "// eslint-disable-next-line no-shadow\n";
        $typescript_endpoint_enum .= "export enum {$name}Endpoint {\n";
        $typescript_request_types .= "export interface {$name}Requests extends {$name}EndpointMapping {\n";
        $typescript_response_types .= "export interface {$name}Responses extends {$name}EndpointMapping {\n";
        foreach ($this->endpoints as $endpoint_name => $endpoint_definition) {
            $endpoint = $endpoint_definition();
            $typescript_endpoint_enum .= "    {$endpoint_name} = '{$endpoint_name}',\n";

            $typescript_request_types .= "    {$endpoint_name}: ";
            $request_field = $endpoint->getRequestField();
            foreach ($request_field->getExportedTypeScriptTypes() as $type_ident => $exported_type) {
                $typescript_exported_types .= "export type {$type_ident} = {$exported_type};\n";
            }
            $request_type = $request_field->getTypeScriptType(['should_substitute' => true]);
            $indented_request_type = str_replace("\n", "\n        ", $request_type);
            $typescript_request_types .= $indented_request_type;
            $typescript_request_types .= ",\n";

            $typescript_response_types .= "    {$endpoint_name}: ";
            $response_field = $endpoint->getResponseField();
            foreach ($response_field->getExportedTypeScriptTypes() as $type_ident => $exported_type) {
                $typescript_exported_types .= "export type {$type_ident} = {$exported_type};\n";
            }
            $response_type = $response_field->getTypeScriptType(['should_substitute' => true]);
            $indented_response_type = str_replace("\n", "\n        ", $response_type);
            $typescript_response_types .= $indented_response_type;
            $typescript_response_types .= ",\n";
        }
        $typescript_endpoint_enum .= "}\n";
        $typescript_request_types .= "}\n";
        $typescript_response_types .= "}\n";

        $typescript_output .= "{$typescript_exported_types}\n";
        $typescript_output .= "{$typescript_endpoint_enum}\n";
        $typescript_output .= "type {$name}EndpointMapping = {[key in {$name}Endpoint]: {[fieldId: string]: any}};\n\n";
        $typescript_output .= "{$typescript_request_types}\n";
        $typescript_output .= "{$typescript_response_types}\n";
        return $typescript_output;
    }

    public function serve() {
        global $_SERVER;
        $endpoint_name = $this->getSanitizedEndpointName($_SERVER['PATH_INFO']);
        $this->serveEndpoint($endpoint_name);
    }

    protected function serveEndpoint($endpoint_name) {
        try {
            if ($this->logger) {
                $handler = new \Monolog\ErrorHandler($this->logger);
                $handler->registerErrorHandler();
                $handler->registerExceptionHandler();
            }
            if (!isset($this->endpoints[$endpoint_name])) {
                throw new HttpError(400, 'Invalid endpoint');
            }
            $endpoint = $this->endpoints[$endpoint_name]();
            if ($this->logger) {
                $endpoint->setLogger($this->logger);
            } else {
                $endpoint->setLogger(new \Monolog\Logger('NullLogger'));
            }
            $endpoint->setup();
            $input = $endpoint->parseInput();
            $result = $endpoint->call($input);
            return $this->respond(200, $result);
        } catch (HttpError $httperr) {
            return $this->respond(
                $httperr->getCode(),
                $httperr->getStructuredAnswer()
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
            throw new HttpError(400, 'No path info');
        }
        return $path_info_matches[1];
    }

    protected function respond($http_code, $response) {
        http_response_code($http_code);
        exit(json_encode($response));
    }
}
