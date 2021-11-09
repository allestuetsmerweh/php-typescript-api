<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes;
use PhpTypeScriptApi\Fields\FieldUtils;
use PhpTypeScriptApi\Fields\ValidationError;

require_once __DIR__.'/../_common/UnitTestCase.php';

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldUtils
 */
final class FieldUtilsTest extends UnitTestCase {
    public function testValidateMissingInput(): void {
        $field_utils = FieldUtils::create();
        try {
            $field_utils->validate(new FieldTypes\Field(['allow_null' => false]), null);
            $this->fail('Error expected');
        } catch (ValidationError $err) {
            $this->assertMatchesRegularExpression('/^Validation Error: /', $err->getMessage());
            $this->assertSame([
                '.' => ["Field can not be empty."],
            ], $err->getValidationErrors());
        }
    }

    public function testValidateValidInput(): void {
        $field_utils = FieldUtils::create();
        $validated = $field_utils->validate(new FieldTypes\Field(['allow_null' => false]), 'test');
        $this->assertSame('test', $validated);
    }

    public function testValidateParsed(): void {
        $field_utils = FieldUtils::create();
        try {
            $field_utils->validate(
                new FieldTypes\Field(['allow_null' => false]), '', ['parse' => true]);
            $this->fail('Error expected');
        } catch (ValidationError $err) {
            $this->assertMatchesRegularExpression('/^Validation Error: /', $err->getMessage());
            $this->assertSame([
                '.' => ["Field can not be empty."],
            ], $err->getValidationErrors());
        }
    }

    public function testValidateUnparseable(): void {
        $field_utils = FieldUtils::create();
        try {
            $field_utils->validate(
                new FieldTypes\IntegerField([]), 'not_an_integer', ['parse' => true]);
            $this->fail('Error expected');
        } catch (ValidationError $err) {
            $this->assertMatchesRegularExpression('/^Validation Error: /', $err->getMessage());
            $this->assertSame([
                '.' => ["Illegible integer: not_an_integer"],
            ], $err->getValidationErrors());
        }
    }
}
