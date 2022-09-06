<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields\Types;

use PhpTypeScriptApi\Fields\FieldTypes\TimeField;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\TimeField
 */
final class TimeFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new TimeField([]);
        $this->assertSame('string', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new TimeField(['allow_null' => true]);
        $this->assertSame('string|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new TimeField(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new TimeField([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new TimeField([]);
        $this->assertSame('test', $field->parse('test'));
        $this->assertSame(null, $field->parse(''));
        $this->assertSame(null, $field->parse(null));
    }

    public function testMinValueDefault(): void {
        $field = new TimeField([]);
        $this->assertSame(null, $field->getMinValue());
    }

    public function testMinValueSet(): void {
        $field = new TimeField(['min_value' => '13:27:00']);
        $this->assertSame('13:27:00', $field->getMinValue());
    }

    public function testMaxValueDefault(): void {
        $field = new TimeField([]);
        $this->assertSame(null, $field->getMaxValue());
    }

    public function testMaxValueSet(): void {
        $field = new TimeField(['max_value' => '13:27:00']);
        $this->assertSame('13:27:00', $field->getMaxValue());
    }

    public function testValidatesMinValue(): void {
        $field = new TimeField(['min_value' => '13:27:00']);
        $this->assertSame(
            ['.' => ['Value must not be less than 13:27:00.']],
            $field->getValidationErrors('13:26:59')
        );
        $this->assertSame([], $field->getValidationErrors('13:27:00'));
        $this->assertSame([], $field->getValidationErrors('13:27:01'));
    }

    public function testValidatesMaxValue(): void {
        $field = new TimeField(['max_value' => '13:27:00']);
        $this->assertSame([], $field->getValidationErrors('13:26:59'));
        $this->assertSame([], $field->getValidationErrors('13:27:00'));
        $this->assertSame(
            ['.' => ['Value must not be greater than 13:27:00.']],
            $field->getValidationErrors('13:27:01')
        );
    }

    public function testValidatesWeirdValues(): void {
        $field = new TimeField([]);
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
            ['.' => ['Value must have pattern /^[0-9]{2}:[0-9]{2}:[0-9]{2}$/.']],
            $field->getValidationErrors('test')
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
