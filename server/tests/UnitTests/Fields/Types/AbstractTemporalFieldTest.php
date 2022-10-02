<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Fields\Types;

use PhpTypeScriptApi\Fields\FieldTypes\AbstractTemporalField;
use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;

class FakeTemporalField extends AbstractTemporalField {
    protected function getRegex() {
        return '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
    }
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\AbstractTemporalField
 */
final class AbstractTemporalFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new FakeTemporalField([]);
        $this->assertSame('string', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new FakeTemporalField(['allow_null' => true]);
        $this->assertSame('string|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new FakeTemporalField(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new FakeTemporalField([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'string|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new FakeTemporalField([]);
        $this->assertSame('test', $field->parse('test'));
        $this->assertSame(null, $field->parse(''));
        $this->assertSame(null, $field->parse(null));
    }

    public function testMinValueDefault(): void {
        $field = new FakeTemporalField([]);
        $this->assertSame(null, $field->getMinValue());
    }

    public function testMinValueSet(): void {
        $field = new FakeTemporalField(['min_value' => '2020-03-13']);
        $this->assertSame('2020-03-13', $field->getMinValue());
    }

    public function testMaxValueDefault(): void {
        $field = new FakeTemporalField([]);
        $this->assertSame(null, $field->getMaxValue());
    }

    public function testMaxValueSet(): void {
        $field = new FakeTemporalField(['max_value' => '2020-03-13']);
        $this->assertSame('2020-03-13', $field->getMaxValue());
    }

    public function testValidatesMinValue(): void {
        $field = new FakeTemporalField(['min_value' => '2020-03-13']);
        $this->assertSame(
            ['.' => ['Value must not be less than 2020-03-13.']],
            $field->getValidationErrors('2020-03-12')
        );
        $this->assertSame([], $field->getValidationErrors('2020-03-13'));
        $this->assertSame([], $field->getValidationErrors('2020-03-14'));
    }

    public function testValidatesMaxValue(): void {
        $field = new FakeTemporalField(['max_value' => '2020-03-13']);
        $this->assertSame([], $field->getValidationErrors('2020-03-12'));
        $this->assertSame([], $field->getValidationErrors('2020-03-13'));
        $this->assertSame(
            ['.' => ['Value must not be greater than 2020-03-13.']],
            $field->getValidationErrors('2020-03-14')
        );
    }

    public function testValidatesWeirdValues(): void {
        $field = new FakeTemporalField([]);
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
            ['.' => ['Value must have pattern /^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.']],
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
