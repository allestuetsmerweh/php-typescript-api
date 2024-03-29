<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields\Types;

use PhpTypeScriptApi\Fields\FieldTypes\ObjectField;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\ObjectField
 */
final class ObjectFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new ObjectField([
            'field_structure' => [
                'one' => new FakeItemField([]),
                'two' => new FakeItemField([]),
            ],
        ]);
        $this->assertSame("{\n    'one': ItemType,\n    'two': ItemType,\n}", $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeEmpty(): void {
        $field = new ObjectField([
            'field_structure' => [],
        ]);
        $this->assertSame("Record<string, never>", $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new ObjectField([
            'field_structure' => ['test' => new FakeItemField([])],
            'allow_null' => true,
        ]);
        $this->assertSame("{\n    'test': ItemType,\n}|null", $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeEmptyWithNullAllowed(): void {
        $field = new ObjectField([
            'field_structure' => [],
            'allow_null' => true,
        ]);
        $this->assertSame("Record<string, never>|null", $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowedInItem(): void {
        $field = new ObjectField([
            'field_structure' => ['test' => new FakeItemField(['allow_null' => true])],
        ]);
        $this->assertSame("{\n    'test': ItemType|null,\n}", $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedItemTypeScriptType(): void {
        $field = new ObjectField([
            'field_structure' => [
                'one' => new FakeItemField(['export_as' => 'ExportedType1']),
                'two' => new FakeItemField(['export_as' => 'ExportedType2']),
            ],
        ]);
        $this->assertSame(
            "{\n    'one': ExportedType1,\n    'two': ExportedType2,\n}",
            $field->getTypeScriptType()
        );
        $this->assertSame([
            'ExportedType1' => 'ItemType',
            'ExportedType2' => 'ItemType',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new ObjectField([
            'field_structure' => [
                'test' => new FakeItemField([]),
            ],
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame("ExportedType", $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => "{\n    'test': ItemType,\n}|null",
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedItemTypeScriptTypeWithNullAllowed(): void {
        $field = new ObjectField([
            'field_structure' => [
                'test' => new FakeItemField(['export_as' => 'ExportedType']),
            ],
            'allow_null' => true,
        ]);
        $this->assertSame("{\n    'test': ExportedType,\n}|null", $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'ItemType',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedItemTypeScriptTypeWithNullAllowedInItem(): void {
        $field = new ObjectField([
            'field_structure' => [
                'test' => new FakeItemField([
                    'allow_null' => true,
                    'export_as' => 'ExportedType',
                ]),
            ],
        ]);
        $this->assertSame("{\n    'test': ExportedType,\n}", $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'ItemType|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new ObjectField([]);
        try {
            $field->parse('test');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame("Unlesbares Feld: ObjectField", $exc->getMessage());
        }
    }

    public function testItemFieldDefault(): void {
        $field = new ObjectField([]);
        $this->assertSame([], $field->getFieldStructure());
    }

    public function testFieldStructureSet(): void {
        $test_item_field = new FakeItemField([]);
        $field = new ObjectField([
            'field_structure' => ['test' => $test_item_field],
        ]);
        $this->assertSame(['test' => $test_item_field], $field->getFieldStructure());
    }

    public function testInvalidFieldStructure(): void {
        try {
            new ObjectField([
                'field_structure' => ['test' => 'not_a_field'],
            ]);
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame("`field_structure`['test'] must be an instance of `Field`", $exc->getMessage());
        }
    }

    public function testValidatesFieldStructure(): void {
        $field = new ObjectField([
            'field_structure' => [
                'one' => new FakeItemField([]),
                'two' => new FakeItemField([]),
                'three' => new FakeItemField(['allow_null' => true]),
            ],
        ]);
        $this->assertSame(
            [
                'one' => [['.' => ["Wert muss 'foo' oder 'bar' sein."]]],
                'two' => [['.' => ["Wert muss 'foo' oder 'bar' sein."]]],
                'three' => [['.' => ["Wert muss 'foo' oder 'bar' sein."]]],
            ],
            $field->getValidationErrors([
                'one' => 'Eins',
                'two' => 'Zwei',
                'three' => 'Drei',
            ])
        );
        $this->assertSame(
            [
                'two' => [['.' => ["Wert muss 'foo' oder 'bar' sein."]]],
            ],
            $field->getValidationErrors([
                'one' => 'foo',
                'two' => 'Zwei',
                'three' => 'bar',
            ])
        );
        $this->assertSame(
            [
                'one' => [['.' => ["Field can not be empty."]]],
            ],
            $field->getValidationErrors([
                'one' => null,
                'two' => 'foo',
                'three' => null,
            ])
        );
        $this->assertSame(
            [
                'one' => ["Missing key: one."],
                'three' => ["Missing key: three."],
                '.' => ["Unknown key: additional_key."],
            ],
            $field->getValidationErrors([
                'two' => 'foo',
                'additional_key' => 'WTF?',
            ])
        );
        $this->assertSame(
            [
                'three' => ["Missing key: three."],
            ],
            $field->getValidationErrors([
                'one' => 'foo',
                'two' => 'bar',
            ])
        );
        $this->assertSame([
            'one' => ["Missing key: one."],
            'two' => ["Missing key: two."],
            'three' => ["Missing key: three."],
        ], $field->getValidationErrors([]));
        $this->assertSame(
            ['.' => ["Value must be an object."]],
            $field->getValidationErrors('not_an_object')
        );
        $this->assertSame(
            ['.' => ["Field can not be empty."]],
            $field->getValidationErrors(null)
        );
    }

    public function testValidatesNullableObjectField(): void {
        $field = new ObjectField([
            'field_structure' => ['test' => new FakeItemField([])],
            'allow_null' => true,
        ]);
        $this->assertSame(
            [
                'test' => ["Missing key: test."],
                '.' => ["Unknown key: additional_key."],
            ],
            $field->getValidationErrors(['additional_key' => 'WTF?'])
        );
        $this->assertSame(
            [
                'test' => [['.' => ["Field can not be empty."]]],
            ],
            $field->getValidationErrors(['test' => null])
        );
        $this->assertSame([], $field->getValidationErrors(['test' => 'foo']));
        $this->assertSame(
            [
                'test' => ["Missing key: test."],
            ],
            $field->getValidationErrors([])
        );
        $this->assertSame(
            [
                '.' => ["Value must be an object."],
            ],
            $field->getValidationErrors('not_an_object')
        );
        $this->assertSame([], $field->getValidationErrors(null));
    }
}
