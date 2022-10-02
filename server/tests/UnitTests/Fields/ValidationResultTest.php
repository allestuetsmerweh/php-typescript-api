<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields;

use PhpTypeScriptApi\Fields\ValidationResult;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\Fields\ValidationResult
 */
final class ValidationResultTest extends UnitTestCase {
    public function testEmptyValidationResult(): void {
        $result = ValidationResult::create();
        $this->assertSame(true, $result->isValid());
        $this->assertSame([], $result->getErrors());
    }

    public function testRecordError(): void {
        $result = ValidationResult::create();
        $result->recordError("There is an error.");
        $this->assertSame(false, $result->isValid());
        $this->assertSame(
            ['.' => ["There is an error."]],
            $result->getErrors()
        );
    }

    public function testRecordErrorInKey(): void {
        $result = ValidationResult::create();
        $result->recordErrorInKey('key', "Error in key.");
        $this->assertSame(false, $result->isValid());
        $this->assertSame(
            ['key' => ["Error in key."]],
            $result->getErrors()
        );
    }
}
