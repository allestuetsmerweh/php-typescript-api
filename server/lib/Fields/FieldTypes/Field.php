<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

class Field {
    private bool $allow_null = false;
    private mixed $default_value;
    protected ?string $export_as = null;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = []) {
        $this->allow_null = $config['allow_null'] ?? false;
        $this->default_value = $config['default_value'] ?? null;
        $this->export_as = $config['export_as'] ?? null;
    }

    public function getAllowNull(): bool {
        return $this->allow_null;
    }

    public function getDefaultValue(): mixed {
        return $this->default_value;
    }

    /** @return array<string, array<array<mixed>|string>> */
    public function getValidationErrors(mixed $value): array {
        $validation_result = $this->validate($value);
        return $validation_result->getErrors();
    }

    protected function validate(mixed $value): Fields\ValidationResult {
        $validation_result = Fields\ValidationResult::create();
        if (!$this->allow_null) {
            if ($value === null) {
                if ($this->default_value === null) {
                    $validation_result->recordError(Translator::__('fields.must_not_be_empty'));
                }
            }
        }
        return $validation_result;
    }

    public function parse(?string $string): mixed {
        if ($string === null || $string === '') {
            return null;
        }
        return $string;
    }

    /** @param array<string, mixed> $config */
    public function getTypeScriptType(array $config = []): string {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return 'unknown';
    }

    /** @return array<string, string> */
    public function getExportedTypeScriptTypes(): array {
        if ($this->export_as !== null) {
            return [
                $this->export_as => $this->getTypeScriptType([
                    'should_substitute' => false,
                ]),
            ];
        }
        return [];
    }
}
