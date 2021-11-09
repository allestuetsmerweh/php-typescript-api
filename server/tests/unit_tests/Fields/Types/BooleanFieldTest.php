<?php

declare(strict_types=1);

use PhpTypeScriptApi\Fields\FieldTypes\BooleanField;

require_once __DIR__.'/../../_common/UnitTestCase.php';

/**
 * @internal
 * @covers \PhpTypeScriptApi\Fields\FieldTypes\BooleanField
 */
final class BooleanFieldTest extends UnitTestCase {
    public function testTypeScriptType(): void {
        $field = new BooleanField([]);
        $this->assertSame('boolean', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testTypeScriptTypeWithNullAllowed(): void {
        $field = new BooleanField(['allow_null' => true]);
        $this->assertSame('boolean|null', $field->getTypeScriptType());
        $this->assertSame([], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptType(): void {
        $field = new BooleanField(['export_as' => 'ExportedType']);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'boolean',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testSubstitutedTypeScriptTypeWithNullAllowed(): void {
        $field = new BooleanField([
            'allow_null' => true,
            'export_as' => 'ExportedType',
        ]);
        $this->assertSame('ExportedType', $field->getTypeScriptType());
        $this->assertSame([
            'ExportedType' => 'boolean|null',
        ], $field->getExportedTypeScriptTypes());
    }

    public function testParse(): void {
        $field = new BooleanField([]);
        $this->assertSame(true, $field->parse('true'));
        $this->assertSame(true, $field->parse('1'));
        $this->assertSame(false, $field->parse('false'));
        $this->assertSame(false, $field->parse('0'));
        $this->assertSame(null, $field->parse(''));
        $this->assertSame(null, $field->parse(null));
        try {
            $field->parse('test');
            $this->fail('Error expected');
        } catch (\Exception $exc) {
            $this->assertSame("Illegible binary value: test", $exc->getMessage());
        }
    }

    public function testValidatesNullAllowed(): void {
        $field = new BooleanField(['allow_null' => true]);
        $this->assertSame([], $field->getValidationErrors(true));
        $this->assertSame([], $field->getValidationErrors(false));
        $this->assertSame([], $field->getValidationErrors(null));
    }

    public function testValidatesNullDisallowed(): void {
        $field = new BooleanField(['allow_null' => false]);
        $this->assertSame([], $field->getValidationErrors(true));
        $this->assertSame([], $field->getValidationErrors(false));
        $this->assertSame(
            ['.' => ['Field can not be empty.']],
            $field->getValidationErrors(null)
        );
    }

    public function testValidatesWeirdValues(): void {
        $field = new BooleanField([]);
        $this->assertSame(
            ['.' => ['Value must be yes or no.']],
            $field->getValidationErrors(1)
        );
        $this->assertSame(
            ['.' => ['Value must be yes or no.']],
            $field->getValidationErrors('test')
        );
        $this->assertSame(
            ['.' => ['Value must be yes or no.']],
            $field->getValidationErrors([1])
        );
        $this->assertSame(
            ['.' => ['Value must be yes or no.']],
            $field->getValidationErrors([1 => 'one'])
        );
    }
}
