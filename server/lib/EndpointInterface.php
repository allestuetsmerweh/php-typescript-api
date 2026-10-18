<?php

namespace PhpTypeScriptApi;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface EndpointInterface extends \Psr\Log\LoggerAwareInterface {
    public function setup(): void;

    public function runtimeSetup(): void;

    public function setHttpContext(Request $request, Response $response): void;

    public function clearHttpContext(): void;

    public function parseInput(Request $request): mixed;

    public function call(mixed $raw_input): mixed;

    /** @return array<string, string> */
    public function getNamedTsTypes(): array;

    public function getRequestTsType(): string;

    public function getResponseTsType(): string;
}
