<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes\NumberField;

require_once __DIR__.'/../../_common/UnitTestCase.php';

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\NumberField
 */
final class NumberFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new NumberField([]);
        $this->assertSame('number', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new NumberField(['allow_null' => true]);
        $this->assertSame('number|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new NumberField(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'number',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new NumberField([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'number|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new NumberField([]);
        $this->assertSame(0.0, $field->parse('0'));
        $this->assertSame(0.0, $field->parse('0.0'));
        $this->assertSame(12.34, $field->parse('12.34'));
        $this->assertSame(-12.34, $field->parse('-12.34'));
        $this->assertSame(1234.0, $field->parse('1234'));
        $this->assertSame(-1234.0, $field->parse('-1234'));
        $this->assertSame(null, $field->parse(''));
        try {
            $field->parse('test');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame("Unlesbare Zahl: 'test'", $exc->getMessage());
        }
    }

    public function testMinValueDefault(): void {
        $field = new NumberField([]);
        $this->assertSame(null, $field->getMinValue());
    }

    public function testMinValueSet(): void {
        $field = new NumberField(['min_value' => 10.3]);
        $this->assertSame(10.3, $field->getMinValue());
    }

    public function testMaxValueDefault(): void {
        $field = new NumberField([]);
        $this->assertSame(null, $field->getMaxValue());
    }

    public function testMaxValueSet(): void {
        $field = new NumberField(['max_value' => 1.5]);
        $this->assertSame(1.5, $field->getMaxValue());
    }

    public function testValidatesMinValue(): void {
        $field = new NumberField(['min_value' => 2.5]);
        $this->assertSame(
            ['.' => ['Wert darf nicht kleiner als 2.5 sein.']],
            $field->getValidationErrors(2)
        );
        $this->assertSame(
            ['.' => ['Wert darf nicht kleiner als 2.5 sein.']],
            $field->getValidationErrors(2.4999)
        );
        $this->assertSame([], $field->getValidationErrors(2.5));
        $this->assertSame([], $field->getValidationErrors(3));
        $this->assertSame(
            ['.' => ['Feld darf nicht leer sein.']],
            $field->getValidationErrors(null)
        );
    }

    public function testValidatesMaxValue(): void {
        $field = new NumberField(['max_value' => 2.5]);
        $this->assertSame([], $field->getValidationErrors(2));
        $this->assertSame([], $field->getValidationErrors(2.5));
        $this->assertSame(
            ['.' => ['Wert darf nicht grösser als 2.5 sein.']],
            $field->getValidationErrors(2.5001)
        );
        $this->assertSame(
            ['.' => ['Wert darf nicht grösser als 2.5 sein.']],
            $field->getValidationErrors(3)
        );
        $this->assertSame(
            ['.' => ['Feld darf nicht leer sein.']],
            $field->getValidationErrors(null)
        );
    }

    public function testAllowsNullWhenMinValueSet(): void {
        $field = new NumberField(['allow_null' => true, 'min_value' => 2.5]);
        $this->assertSame(
            ['.' => ['Wert darf nicht kleiner als 2.5 sein.']],
            $field->getValidationErrors(2)
        );
        $this->assertSame(
            ['.' => ['Wert darf nicht kleiner als 2.5 sein.']],
            $field->getValidationErrors(2.4999)
        );
        $this->assertSame([], $field->getValidationErrors(2.5));
        $this->assertSame([], $field->getValidationErrors(3));
        $this->assertSame([], $field->getValidationErrors(null));
    }

    public function testAllowsNullWhenMaxValueSet(): void {
        $field = new NumberField(['allow_null' => true, 'max_value' => -2.5]);
        $this->assertSame([], $field->getValidationErrors(-3));
        $this->assertSame([], $field->getValidationErrors(-2.5));
        $this->assertSame(
            ['.' => ['Wert darf nicht grösser als -2.5 sein.']],
            $field->getValidationErrors(-2.4999)
        );
        $this->assertSame(
            ['.' => ['Wert darf nicht grösser als -2.5 sein.']],
            $field->getValidationErrors(-2));
        $this->assertSame([], $field->getValidationErrors(null)
    );
    }

    public function testValidatesWeirdValues(): void {
        $field = new NumberField([]);
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.']],
            $field->getValidationErrors(false)
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.']],
            $field->getValidationErrors(true)
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.']],
            $field->getValidationErrors('test')
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.']],
            $field->getValidationErrors([1])
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.']],
            $field->getValidationErrors([1 => 'one'])
        );
    }
}
