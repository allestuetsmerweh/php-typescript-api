<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes\Field;

require_once __DIR__.'/../../_common/UnitTestCase.php';

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\Field
 */
final class FieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new Field([]);
        $this->assertSame('any', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new Field(['allow_null' => true]);
        $this->assertSame('any', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new Field(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'any',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new Field([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'any',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new Field([]);
        $this->assertSame('test', $field->parse('test'));
        $this->assertSame(null, $field->parse(''));
        $this->assertSame(null, $field->parse(null));
    }

    public function testAllowNullDefault(): void {
        $field = new Field([]);
        $this->assertSame(false, $field->getAllowNull());
    }

    public function testAllowNullTrue(): void {
        $field = new Field(['allow_null' => true]);
        $this->assertSame(true, $field->getAllowNull());
    }

    public function testAllowNullFalse(): void {
        $field = new Field(['allow_null' => false]);
        $this->assertSame(false, $field->getAllowNull());
    }

    public function testDefaultValueDefault(): void {
        $field = new Field([]);
        $this->assertSame(null, $field->getDefaultValue());
    }

    public function testDefaultValueSet(): void {
        $field = new Field(['default_value' => 'test']);
        $this->assertSame('test', $field->getDefaultValue());
    }

    public function testValidatesAllowNullTrue(): void {
        $field = new Field(['allow_null' => true]);
        $this->assertSame([], $field->getValidationErrors(null));
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesAllowNullFalse(): void {
        $field = new Field(['allow_null' => false]);
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesDefaultValueDefault(): void {
        $field = new Field(['allow_null' => false]);
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
        $this->assertSame([], $field->getValidationErrors('test'));
    }

    public function testValidatesDefaultValueSet(): void {
        $field = new Field(['allow_null' => false, 'default_value' => 'test']);
        $this->assertSame([], $field->getValidationErrors(null));
        $this->assertSame([], $field->getValidationErrors('test'));
    }
}
