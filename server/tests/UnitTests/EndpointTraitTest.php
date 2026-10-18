<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Api;
use PhpTypeScriptApi\Endpoint;
use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class FakeHttpContextEndpoint extends Endpoint {
    public function runtimeSetup(): void {
    }

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\Field([]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\Field(['allow_null' => true]);
    }

    protected function handle(mixed $input): mixed {
        $this->getResponse()->headers->set('X-Fake-Header', 'fake-header-value');
        $this->getResponse()->headers->setCookie(new Cookie('fake_cookie', 'fake-cookie-value'));
        return 'fake-output';
    }

    public function testOnlyGetHttpContextRequest(): ?Request {
        return $this->request;
    }
}

class FakeHttpContextErrorEndpoint extends Endpoint {
    public function runtimeSetup(): void {
    }

    public function getResponseField(): FieldTypes\Field {
        return new FieldTypes\Field([]);
    }

    public function getRequestField(): FieldTypes\Field {
        return new FieldTypes\Field(['allow_null' => true]);
    }

    protected function handle(mixed $input): mixed {
        $this->getResponse()->headers->setCookie(new Cookie('fake_cookie', 'fake-cookie-value'));
        throw new HttpError(401, 'Unauthorized');
    }
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\Api
 * @covers \PhpTypeScriptApi\EndpointTrait
 */
final class EndpointTraitTest extends UnitTestCase {
    public function testEndpointCanSetHeadersAndCookies(): void {
        $api = new Api();
        $api->registerEndpoint('fakeHttpContext', new FakeHttpContextEndpoint());

        $request = new Request();
        $request->server->set('PATH_INFO', '/fakeHttpContext');
        $response = $api->getResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('fake-header-value', $response->headers->get('X-Fake-Header'));
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('fake_cookie', $cookies[0]->getName());
        $this->assertSame('fake-cookie-value', $cookies[0]->getValue());
        $this->assertSame('"fake-output"', $response->getContent());
    }

    public function testCookieSurvivesHttpError(): void {
        $api = new Api();
        $api->registerEndpoint('fakeHttpContextError', new FakeHttpContextErrorEndpoint());

        $request = new Request();
        $request->server->set('PATH_INFO', '/fakeHttpContextError');
        $response = $api->getResponse($request);

        $this->assertSame(401, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('fake_cookie', $cookies[0]->getName());
        $this->assertSame('fake-cookie-value', $cookies[0]->getValue());
    }

    public function testHttpContextIsClearedAfterResponse(): void {
        $endpoint = new FakeHttpContextEndpoint();
        $api = new Api();
        $api->registerEndpoint('fakeHttpContext', $endpoint);

        $request = new Request();
        $request->server->set('PATH_INFO', '/fakeHttpContext');
        $api->getResponse($request);

        $this->assertNull($endpoint->testOnlyGetHttpContextRequest());
    }
}
