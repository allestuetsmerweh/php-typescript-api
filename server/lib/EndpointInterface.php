<?php

namespace PhpTypeScriptApi;

use Symfony\Component\HttpFoundation\Request;

interface EndpointInterface extends \Psr\Log\LoggerAwareInterface {
    public function setup(): void;

    public function runtimeSetup(): void;

    public static function getIdent(): string;

    public function parseInput(Request $request): mixed;

    public function call(mixed $raw_input): mixed;

    /** @return array<string, string> */
    public function getNamedTsTypes(): array;

    public function getRequestTsType(): string;

    public function getResponseTsType(): string;
}
