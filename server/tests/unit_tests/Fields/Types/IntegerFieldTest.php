<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes\IntegerField;

require_once __DIR__.'/../../_common/UnitTestCase.php';

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\IntegerField
 */
final class IntegerFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new IntegerField([]);
        $this->assertSame('number', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new IntegerField(['allow_null' => true]);
        $this->assertSame('number|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new IntegerField(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'number',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new IntegerField([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'number|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new IntegerField([]);
        $this->assertSame(0, $field->parse('0'));
        $this->assertSame(1234, $field->parse('1234'));
        $this->assertSame(-1234, $field->parse('-1234'));
        $this->assertSame(null, $field->parse(''));
        try {
            $field->parse('test');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame("Unlesbare Ganzzahl: 'test'", $exc->getMessage());
        }
    }

    public function testMinValueDefault(): void {
        $field = new IntegerField([]);
        $this->assertSame(null, $field->getMinValue());
    }

    public function testMinValueSet(): void {
        $field = new IntegerField(['min_value' => 10]);
        $this->assertSame(10, $field->getMinValue());
    }

    public function testMaxValueDefault(): void {
        $field = new IntegerField([]);
        $this->assertSame(null, $field->getMaxValue());
    }

    public function testMaxValueSet(): void {
        $field = new IntegerField(['max_value' => 10]);
        $this->assertSame(10, $field->getMaxValue());
    }

    public function testValidatesMinValue(): void {
        $field = new IntegerField(['min_value' => 3]);
        $this->assertSame(
            ['.' => ['Wert darf nicht kleiner als 3 sein.']],
            $field->getValidationErrors(2)
        );
        $this->assertSame([], $field->getValidationErrors(3));
        $this->assertSame([], $field->getValidationErrors(4));
        $this->assertSame(
            ['.' => ['Feld darf nicht leer sein.']],
            $field->getValidationErrors(null)
        );
    }

    public function testValidatesMaxValue(): void {
        $field = new IntegerField(['max_value' => 3]);
        $this->assertSame([], $field->getValidationErrors(2));
        $this->assertSame([], $field->getValidationErrors(3));
        $this->assertSame(
            ['.' => ['Wert darf nicht grösser als 3 sein.']],
            $field->getValidationErrors(4)
        );
        $this->assertSame(
            ['.' => ['Feld darf nicht leer sein.']],
            $field->getValidationErrors(null)
        );
    }

    public function testAllowsNullWhenMinValueSet(): void {
        $field = new IntegerField(['allow_null' => true, 'min_value' => 3]);
        $this->assertSame(
            ['.' => ['Wert darf nicht kleiner als 3 sein.']],
            $field->getValidationErrors(2)
        );
        $this->assertSame([], $field->getValidationErrors(3));
        $this->assertSame([], $field->getValidationErrors(4));
        $this->assertSame([], $field->getValidationErrors(null));
    }

    public function testAllowsNullWhenMaxValueSet(): void {
        $field = new IntegerField(['allow_null' => true, 'max_value' => -3]);
        $this->assertSame([], $field->getValidationErrors(-4));
        $this->assertSame([], $field->getValidationErrors(-3));
        $this->assertSame(
            ['.' => ['Wert darf nicht grösser als -3 sein.']],
            $field->getValidationErrors(-2)
        );
        $this->assertSame([], $field->getValidationErrors(null));
    }

    public function testValidatesWeirdValues(): void {
        $field = new IntegerField([]);
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.', 'Wert muss eine Ganzzahl sein.']],
            $field->getValidationErrors(false)
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.', 'Wert muss eine Ganzzahl sein.']],
            $field->getValidationErrors(true)
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.', 'Wert muss eine Ganzzahl sein.']],
            $field->getValidationErrors('test')
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.', 'Wert muss eine Ganzzahl sein.']],
            $field->getValidationErrors([1])
        );
        $this->assertSame(
            ['.' => ['Wert muss eine Zahl sein.', 'Wert muss eine Ganzzahl sein.']],
            $field->getValidationErrors([1 => 'one'])
        );
    }
}
