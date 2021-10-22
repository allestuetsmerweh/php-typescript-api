<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes\StringField;

require_once __DIR__.'/../../_common/UnitTestCase.php';

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
        $this->assertSame([], $field->getValidationErrors(''));
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesAllowEmptyFalse(): void {
        $field = new StringField(['allow_empty' => false]);
        $this->assertSame(
            ['.' => ['Feld darf nicht leer sein.']],
            $field->getValidationErrors('')
        );
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesMaxLength(): void {
        $field = new StringField(['max_length' => 3]);
        $this->assertSame([], $field->getValidationErrors('12'));
        $this->assertSame([], $field->getValidationErrors('123'));
        $this->assertSame(
            ['.' => ['Wert darf maximal 3 Zeichen lang sein.']],
            $field->getValidationErrors('1234')
        );
    }

    public function testValidatesWeirdValues(): void {
        $field = new StringField([]);
        $this->assertSame(
            ['.' => ['Wert muss eine Zeichenkette sein.']],
            $field->getValidationErrors(false)
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zeichenkette sein.']],
            $field->getValidationErrors(true)
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zeichenkette sein.']],
            $field->getValidationErrors(1)
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zeichenkette sein.']],
            $field->getValidationErrors([1])
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zeichenkette sein.']],
            $field->getValidationErrors([1 => 'one'])
        );
    }
}
