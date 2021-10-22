<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes\DateField;

require_once __DIR__.'/../../_common/UnitTestCase.php';

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\DateField
 */
final class DateFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new DateField([]);
        $this->assertSame('string', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new DateField(['allow_null' => true]);
        $this->assertSame('string|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new DateField(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new DateField([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new DateField([]);
        $this->assertSame('test', $field->parse('test'));
        $this->assertSame(null, $field->parse(''));
        $this->assertSame(null, $field->parse(null));
    }

    public function testMinValueDefault(): void {
        $field = new DateField([]);
        $this->assertSame(null, $field->getMinValue());
    }

    public function testMinValueSet(): void {
        $field = new DateField(['min_value' => '2020-03-13']);
        $this->assertSame('2020-03-13', $field->getMinValue());
    }

    public function testMaxValueDefault(): void {
        $field = new DateField([]);
        $this->assertSame(null, $field->getMaxValue());
    }

    public function testMaxValueSet(): void {
        $field = new DateField(['max_value' => '2020-03-13']);
        $this->assertSame('2020-03-13', $field->getMaxValue());
    }

    public function testValidatesMinValue(): void {
        $field = new DateField(['min_value' => '2020-03-13']);
        $this->assertSame(
            ['.' => ['Wert darf nicht kleiner als 2020-03-13 sein.']],
            $field->getValidationErrors('2020-03-12')
        );
        $this->assertSame([], $field->getValidationErrors('2020-03-13'));
        $this->assertSame([], $field->getValidationErrors('2020-03-14'));
    }

    public function testValidatesMaxValue(): void {
        $field = new DateField(['max_value' => '2020-03-13']);
        $this->assertSame([], $field->getValidationErrors('2020-03-12'));
        $this->assertSame([], $field->getValidationErrors('2020-03-13'));
        $this->assertSame(
            ['.' => ['Wert darf nicht grösser als 2020-03-13 sein.']],
            $field->getValidationErrors('2020-03-14')
        );
    }

    public function testValidatesWeirdValues(): void {
        $field = new DateField([]);
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
            ['.' => ['Wert muss im Format /^[0-9]{4}-[0-9]{2}-[0-9]{2}$/ sein.']],
            $field->getValidationErrors('test')
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
