<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

class StringField extends Field {
    private ?int $max_length;
    private bool $allow_empty;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->max_length = $config['max_length'] ?? null;
        $this->allow_empty = $config['allow_empty'] ?? false;
    }

    public function getMaxLength(): ?int {
        return $this->max_length;
    }

    public function getAllowEmpty(): bool {
        return $this->allow_empty;
    }

    protected function validate(mixed $value): Fields\ValidationResult {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_string($value)) {
                $validation_result->recordError(Translator::__('fields.must_be_string'));
            }
        }
        if (!$this->allow_empty) {
            if ($value === '') {
                if ($this->getDefaultValue() === null) {
                    $validation_result->recordError(Translator::__('fields.must_not_be_empty'));
                }
            }
        }
        if ($this->max_length !== null) {
            if ($value !== null && strlen($value) > $this->max_length) {
                $validation_result->recordError(Translator::__(
                    'fields.must_not_be_longer',
                    ['max_length' => "{$this->max_length}"]
                ));
            }
        }
        return $validation_result;
    }

    public function parse(?string $string): mixed {
        return $string;
    }

    /** @param array<string, mixed> $config */
    public function getTypeScriptType(array $config = []): string {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return $this->getAllowNull() ? 'string|null' : 'string';
    }
}
