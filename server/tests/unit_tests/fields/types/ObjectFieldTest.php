<?php

declare(strict_types=1);

require_once __DIR__.'/../../../../lib/fields/types/ObjectField.php';
require_once __DIR__.'/../../common/UnitTestCase.php';
require_once __DIR__.'/FakeItemField.php';

/**
 * @internal
 * @covers \ObjectField
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

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new ObjectField([
            'field_structure' => ['test' => new FakeItemField([])],
            'allow_null' => true,
        ]);
        $this->assertSame("{\n    'test': ItemType,\n}|null", $field->getTypeScriptType());
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
                'one' => [['.' => ["Feld darf nicht leer sein."]]],
            ],
            $field->getValidationErrors([
                'one' => null,
                'two' => 'foo',
                'three' => null,
            ])
        );
        $this->assertSame(
            [
                'one' => [['.' => ["Feld darf nicht leer sein."]]],
                '.' => ["Überflüssiger Schlüssel 'additional_key'."],
            ],
            $field->getValidationErrors([
                'two' => 'foo',
                'additional_key' => 'WTF?',
            ])
        );
        $this->assertSame([], $field->getValidationErrors([
            'one' => 'foo',
            'two' => 'bar',
        ]));
        $this->assertSame([
            'one' => [['.' => ["Feld darf nicht leer sein."]]],
            'two' => [['.' => ["Feld darf nicht leer sein."]]],
        ], $field->getValidationErrors([]));
        $this->assertSame(
            ['.' => ["Wert muss ein Objekt sein."]],
            $field->getValidationErrors('not_an_object')
        );
        $this->assertSame(
            ['.' => ["Feld darf nicht leer sein."]],
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
                'test' => [['.' => ["Feld darf nicht leer sein."]]],
                '.' => ["Überflüssiger Schlüssel 'additional_key'."],
            ],
            $field->getValidationErrors(['additional_key' => 'WTF?'])
        );
        $this->assertSame(
            [
                'test' => [['.' => ["Feld darf nicht leer sein."]]],
            ],
            $field->getValidationErrors(['test' => null])
        );
        $this->assertSame([], $field->getValidationErrors(['test' => 'foo']));
        $this->assertSame(
            [
                'test' => [['.' => ["Feld darf nicht leer sein."]]],
            ],
            $field->getValidationErrors([])
        );
        $this->assertSame(
            [
                '.' => ["Wert muss ein Objekt sein."],
            ],
            $field->getValidationErrors('not_an_object')
        );
        $this->assertSame([], $field->getValidationErrors(null));
    }
}
