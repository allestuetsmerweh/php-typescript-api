<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\ValidationError;

require_once __DIR__.'/../_common/UnitTestCase.php';

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
