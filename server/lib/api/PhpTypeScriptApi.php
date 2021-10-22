<?php

class PhpTypeScriptApi {
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
}
