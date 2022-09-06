<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields\Types;

use PhpTypeScriptApi\Fields\FieldTypes\EnumField;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\EnumField
 */
final class EnumFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new EnumField(['allowed_values' => ['one', 'two', 'three']]);
        $this->assertSame('\'one\'|\'two\'|\'three\'', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new EnumField(['allowed_values' => ['one', 'two', 'three'], 'allow_null' => true]);
        $this->assertSame('\'one\'|\'two\'|\'three\'|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new EnumField([
            'allowed_values' => ['one', 'two', 'three'],
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => '\'one\'|\'two\'|\'three\'',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new EnumField([
            'allowed_values' => ['one', 'two', 'three'],
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => '\'one\'|\'two\'|\'three\'|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new EnumField(['allowed_values' => ['one', 'two', 'three']]);
        $this->assertSame('test', $field->parse('test'));
        $this->assertSame(null, $field->parse(''));
        $this->assertSame(null, $field->parse(null));
    }

    public function testAllowedValuesDefault(): void {
        $field = new EnumField([]);
        $this->assertSame([], $field->getAllowedValues());
    }

    public function testAllowedValuesSet(): void {
        $field = new EnumField(['allowed_values' => ['one', 'two', 'three']]);
        $this->assertSame(['one', 'two', 'three'], $field->getAllowedValues());
    }

    public function testValidatesAllowedValue(): void {
        $field = new EnumField(['allowed_values' => ['one', 'two', 'three']]);
        $this->assertSame([], $field->getValidationErrors('one'));
        $this->assertSame([], $field->getValidationErrors('two'));
        $this->assertSame([], $field->getValidationErrors('three'));
    }

    public function testValidatesDisallowedValue(): void {
        $field = new EnumField(['allowed_values' => ['one', 'two', 'three']]);
        $this->assertSame(
            ['.' => ['Value must be among the allowed values.']],
            $field->getValidationErrors('zero')
        );
        $this->assertSame(
            ['.' => ['Value must be among the allowed values.']],
            $field->getValidationErrors('four')
        );
        $this->assertSame(
            ['.' => ['Value must be among the allowed values.']],
            $field->getValidationErrors('')
        );
    }

    public function testValidatesWeirdValues(): void {
        $field = new EnumField([]);
        $this->assertSame(
            ['.' => ['Value must be among the allowed values.']],
            $field->getValidationErrors(false)
        );
        $this->assertSame(
            ['.' => ['Value must be among the allowed values.']],
            $field->getValidationErrors(true)
        );
        $this->assertSame(
            ['.' => ['Value must be among the allowed values.']],
            $field->getValidationErrors(1)
        );
        $this->assertSame(
            ['.' => ['Value must be among the allowed values.']],
            $field->getValidationErrors('test')
        );
        $this->assertSame(
            ['.' => ['Value must be simple.']],
            $field->getValidationErrors([1])
        );
        $this->assertSame(
            ['.' => ['Value must be simple.']],
            $field->getValidationErrors([1 => 'one'])
        );
    }
}
