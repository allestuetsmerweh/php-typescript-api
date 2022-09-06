<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields\Types;

use PhpTypeScriptApi\Fields\FieldTypes\StringField;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\StringField
 */
final class StringFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new StringField([]);
        $this->assertSame('string', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new StringField(['allow_null' => true]);
        $this->assertSame('string|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new StringField(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new StringField([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new StringField([]);
        $this->assertSame('test', $field->parse('test'));
        $this->assertSame('', $field->parse(''));
        $this->assertSame(null, $field->parse(null));
    }

    public function testAllowEmptyDefault(): void {
        $field = new StringField([]);
        $this->assertSame(false, $field->getAllowEmpty());
    }

    public function testAllowEmptyTrue(): void {
        $field = new StringField(['allow_empty' => true]);
        $this->assertSame(true, $field->getAllowEmpty());
    }

    public function testAllowEmptyFalse(): void {
        $field = new StringField(['allow_empty' => false]);
        $this->assertSame(false, $field->getAllowEmpty());
    }

    public function testMaxLengthDefault(): void {
        $field = new StringField([]);
        $this->assertSame(null, $field->getMaxLength());
    }

    public function testMaxLengthSet(): void {
        $field = new StringField(['max_length' => 10]);
        $this->assertSame(10, $field->getMaxLength());
    }

    public function testValidatesAllowEmptyTrue(): void {
        $field = new StringField(['allow_empty' => true]);
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
        $this->assertSame([], $field->getValidationErrors(''));
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesAllowEmptyTrueForAllowedNull(): void {
        $field = new StringField(['allow_null' => true, 'allow_empty' => true]);
        $this->assertSame([], $field->getValidationErrors(null));
        $this->assertSame([], $field->getValidationErrors(''));
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesAllowEmptyTrueForDisallowedNull(): void {
        $field = new StringField(['allow_null' => false, 'allow_empty' => true]);
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
        $this->assertSame([], $field->getValidationErrors(''));
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesAllowEmptyFalse(): void {
        $field = new StringField(['allow_empty' => false]);
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors('')
        );
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesAllowEmptyFalseForAllowedNull(): void {
        $field = new StringField(['allow_null' => true, 'allow_empty' => false]);
        $this->assertSame([], $field->getValidationErrors(null));
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors('')
        );
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesAllowEmptyFalseForDisallowedNull(): void {
        $field = new StringField(['allow_null' => false, 'allow_empty' => false]);
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors('')
        );
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesMaxLength(): void {
        $field = new StringField(['max_length' => 3]);
        $this->assertSame([], $field->getValidationErrors('12'));
        $this->assertSame([], $field->getValidationErrors('123'));
        $this->assertSame(
            ['.' => ['Value can not be longer than 3 characters.']],
            $field->getValidationErrors('1234')
        );
    }

    public function testValidatesMaxLengthForAllowedNull(): void {
        $field = new StringField(['allow_null' => true, 'max_length' => 3]);
        $this->assertSame([], $field->getValidationErrors(null));
    }

    public function testValidatesMaxLengthForDisallowedNull(): void {
        $field = new StringField(['allow_null' => false, 'max_length' => 3]);
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
    }

    public function testValidatesWeirdValues(): void {
        $field = new StringField([]);
        $this->assertSame(
            ['.' => ['Value must be a string.']],
            $field->getValidationErrors(false)
        );
        $this->assertSame(
            ['.' => ['Value must be a string.']],
            $field->getValidationErrors(true)
        );
        $this->assertSame(
            ['.' => ['Value must be a string.']],
            $field->getValidationErrors(1)
        );
        $this->assertSame(
            ['.' => ['Value must be a string.']],
            $field->getValidationErrors([1])
        );
        $this->assertSame(
            ['.' => ['Value must be a string.']],
            $field->getValidationErrors([1 => 'one'])
        );
    }
}
