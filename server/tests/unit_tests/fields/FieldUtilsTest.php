<?php

declare(strict_types=1);

require_once __DIR__.'/../../../lib/fields/types/Field.php';
require_once __DIR__.'/../../../lib/fields/types/IntegerField.php';
require_once __DIR__.'/../../../lib/fields/FieldUtils.php';
require_once __DIR__.'/../../../lib/fields/ValidationError.php';
require_once __DIR__.'/../common/UnitTestCase.php';

/**
 * @internal
 * @covers \FieldUtils
 */
final class FieldUtilsTest extends UnitTestCase {
    public function testValidateMissingInput(): void {
        $field_utils = FieldUtils::create();
        try {
            $field_utils->validate(new Field(['allow_null' => false]), null);
            $this->fail('Error expected');
        } catch (ValidationError $err) {
            $this->assertMatchesRegularExpression('/^Validation Error: /', $err->getMessage());
            $this->assertSame([
                '.' => ["Feld darf nicht leer sein."],
            ], $err->getValidationErrors());
        }
    }

    public function testValidateValidInput(): void {
        $field_utils = FieldUtils::create();
        $validated = $field_utils->validate(new Field(['allow_null' => false]), 'test');
        $this->assertSame('test', $validated);
    }

    public function testValidateParsed(): void {
        $field_utils = FieldUtils::create();
        try {
            $field_utils->validate(
                new Field(['allow_null' => false]), '', ['parse' => true]);
            $this->fail('Error expected');
        } catch (ValidationError $err) {
            $this->assertMatchesRegularExpression('/^Validation Error: /', $err->getMessage());
            $this->assertSame([
                '.' => ["Feld darf nicht leer sein."],
            ], $err->getValidationErrors());
        }
    }

    public function testValidateUnparseable(): void {
        $field_utils = FieldUtils::create();
        try {
            $field_utils->validate(
                new IntegerField([]), 'not_an_integer', ['parse' => true]);
            $this->fail('Error expected');
        } catch (ValidationError $err) {
            $this->assertMatchesRegularExpression('/^Validation Error: /', $err->getMessage());
            $this->assertSame([
                '.' => ["Unlesbare Ganzzahl: 'not_an_integer'"],
            ], $err->getValidationErrors());
        }
    }
}
