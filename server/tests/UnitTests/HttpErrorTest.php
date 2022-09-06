<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\HttpError;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 * @covers \PhpTypeScriptApi\HttpError
 */
final class HttpErrorTest extends UnitTestCase {
    public function testHttp404Error(): void {
        $error = new HttpError(404, 'Not found');

        $this->assertSame(404, $error->getCode());
        $this->assertSame('Not found', $error->getMessage());
        $this->assertSame([
            'message' => 'Not found',
            'error' => true,
        ], $error->getStructuredAnswer());
    }

    public function testHttpValidationError(): void {
        $validation_error = new ValidationError(['.' => ['root error']]);
        $error = new HttpError(400, 'Bad request', $validation_error);

        $this->assertSame(400, $error->getCode());
        $this->assertSame('Bad request', $error->getMessage());
        $this->assertSame([
            'message' => 'Bad request',
            'error' => [
                'type' => 'ValidationError',
                'validationErrors' => ['.' => ['root error']],
            ],
        ], $error->getStructuredAnswer());
    }
}
