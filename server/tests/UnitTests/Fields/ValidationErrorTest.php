<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields;

use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\ValidationError
 */
final class ValidationErrorTest extends UnitTestCase {
    public function testValidationError(): void {
        $error = new ValidationError(['.' => ['root error']]);

        $this->assertSame(0, $error->getCode());
        $this->assertSame(
            'Validation Error: {".":["root error"]}',
            $error->getMessage()
        );
        $this->assertSame(
            ['.' => ['root error']],
            $error->getValidationErrors()
        );
        $this->assertSame(
            [
                'type' => 'ValidationError',
                'validationErrors' => ['.' => ['root error']],
            ],
            $error->getStructuredAnswer()
        );
    }
}
