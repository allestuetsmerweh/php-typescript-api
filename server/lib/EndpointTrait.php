<?php

namespace PhpTypeScriptApi;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait EndpointTrait {
    protected ?Request $request = null;
    protected ?Response $response = null;

    public function setHttpContext(Request $request, Response $response): void {
        $this->request = $request;
        $this->response = $response;
    }

    public function clearHttpContext(): void {
        $this->request = null;
        $this->response = null;
    }

    protected function getRequest(): Request {
        if ($this->request === null) {
            throw new \LogicException('No HTTP request context available.');
        }
        return $this->request;
    }

    protected function getResponse(): Response {
        if ($this->response === null) {
            throw new \LogicException('No HTTP response context available.');
        }
        return $this->response;
    }
}
