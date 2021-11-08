<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes\ChoiceField;

require_once __DIR__.'/../../_common/UnitTestCase.php';
require_once __DIR__.'/FakeItemField.php';

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\ChoiceField
 */
final class ChoiceFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new ChoiceField([
            'field_map' => [
                'action1' => new FakeItemField([]),
                'action2' => new FakeItemField([]),
            ],
        ]);
        $this->assertSame(<<<'ZZZZZZZZZZ'
        {
            'action1': ItemType,
        }|{
            'action2': ItemType,
        }
        ZZZZZZZZZZ, $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new ChoiceField([
            'field_map' => [
                'type1' => new FakeItemField([]),
                'type2' => new FakeItemField([]),
            ],
            'allow_null' => true,
        ]);
        $this->assertSame(<<<'ZZZZZZZZZZ'
        {
            'type1': ItemType,
        }|{
            'type2': ItemType,
        }|null
        ZZZZZZZZZZ, $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowedInItem(): void {
        $field = new ChoiceField([
            'field_map' => ['test' => new FakeItemField(['allow_null' => true])],
        ]);
        $this->assertSame(<<<'ZZZZZZZZZZ'
        {
            'test': ItemType|null,
        }
        ZZZZZZZZZZ, $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedItemTypeScriptType(): void {
        $field = new ChoiceField([
            'field_map' => [
                'one' => new FakeItemField(['export_as' => 'ExportedType1']),
                'two' => new FakeItemField(['export_as' => 'ExportedType2']),
            ],
        ]);
        $this->assertSame(<<<'ZZZZZZZZZZ'
        {
            'one': ExportedType1,
        }|{
            'two': ExportedType2,
        }
        ZZZZZZZZZZ, $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType1' => 'ItemType',
            'ExportedType2' => 'ItemType',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new ChoiceField([
            'field_map' => [
                'test' => new FakeItemField([]),
            ],
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame("ExportedType", $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => <<<'ZZZZZZZZZZ'
            {
                'test': ItemType,
            }|null
            ZZZZZZZZZZ,
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedItemTypeScriptTypeWithNullAllowed(): void {
        $field = new ChoiceField([
            'field_map' => [
                'test' => new FakeItemField(['export_as' => 'ExportedType']),
            ],
            'allow_null' => true,
        ]);
        $this->assertSame(<<<'ZZZZZZZZZZ'
        {
            'test': ExportedType,
        }|null
        ZZZZZZZZZZ, $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'ItemType',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedItemTypeScriptTypeWithNullAllowedInItem(): void {
        $field = new ChoiceField([
            'field_map' => [
                'test' => new FakeItemField([
                    'allow_null' => true,
                    'export_as' => 'ExportedType',
                ]),
            ],
        ]);
        $this->assertSame(<<<'ZZZZZZZZZZ'
        {
            'test': ExportedType,
        }
        ZZZZZZZZZZ, $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'ItemType|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new ChoiceField([]);
        try {
            $field->parse('test');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame("Unlesbares Feld: ChoiceField", $exc->getMessage());
        }
    }

    public function testConstructorDefault(): void {
        $field = new ChoiceField([]);
        $this->assertSame([], $field->getFieldMap());
    }

    public function testConstructorValid(): void {
        $payload_field = new FakeItemField([]);
        $field = new ChoiceField([
            'field_map' => [
                'action1' => $payload_field,
                'action2' => $payload_field,
            ],
        ]);
        $this->assertSame([
            'action1' => $payload_field,
            'action2' => $payload_field,
        ], $field->getFieldMap());
    }

    public function testInvalidFieldMapWrongType(): void {
        try {
            new ChoiceField([
                'field_map' => ['type' => 'not_a_field'],
            ]);
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame("Field für Schlüssel 'type' muss ein Field sein.", $exc->getMessage());
        }
    }

    public function testValidatesField(): void {
        $field = new ChoiceField([
            'field_map' => [
                'one' => new FakeItemField([]),
                'two' => new FakeItemField([]),
                'three' => new FakeItemField(['allow_null' => true]),
            ],
        ]);
        $this->assertSame([
            'one' => [['.' => ["Wert muss 'foo' oder 'bar' sein."]]],
        ], $field->getValidationErrors([
            'one' => 'Eins',
        ]));
        $this->assertSame([], $field->getValidationErrors([
            'one' => 'foo',
        ]));
        $this->assertSame([
            'two' => [['.' => ["Feld darf nicht leer sein."]]],
        ], $field->getValidationErrors([
            'two' => null,
        ]));
        $this->assertSame([
            '.' => ["Wert muss ein Objekt mit genau einem Schlüssel sein."],
        ], $field->getValidationErrors([
            'two' => 'bar',
            'additional_key' => 'WTF?',
        ]));
        $this->assertSame([
            '.' => ["Unbekannter Schlüssel: 'unknown_key'."],
        ], $field->getValidationErrors([
            'unknown_key' => 'WTF?',
        ]));
        $this->assertSame([
            '.' => ["Wert muss ein Objekt mit genau einem Schlüssel sein."],
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

    public function testValidatesNullableChoiceField(): void {
        $field = new ChoiceField([
            'field_map' => ['test' => new FakeItemField([])],
            'allow_null' => true,
        ]);
        $this->assertSame([
            '.' => ["Wert muss ein Objekt mit genau einem Schlüssel sein."],
        ], $field->getValidationErrors([
            'test' => 'foo',
            'additional_key' => 'WTF?',
        ]));
        $this->assertSame([
            '.' => ["Unbekannter Schlüssel: 'unknown_key'."],
        ], $field->getValidationErrors(['unknown_key' => 'WTF?']));
        $this->assertSame([
            'test' => [['.' => ["Feld darf nicht leer sein."]]],
        ], $field->getValidationErrors(['test' => null]));
        $this->assertSame([], $field->getValidationErrors(['test' => 'foo']));
        $this->assertSame([
            '.' => ["Wert muss ein Objekt mit genau einem Schlüssel sein."],
        ], $field->getValidationErrors([]));
        $this->assertSame([
            '.' => ["Wert muss ein Objekt sein."],
        ], $field->getValidationErrors('not_an_object'));
        $this->assertSame([], $field->getValidationErrors(null));
    }
}
