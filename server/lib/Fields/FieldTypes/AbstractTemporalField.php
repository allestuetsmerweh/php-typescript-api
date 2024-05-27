<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

use PhpTypeScriptApi\Fields;
use PhpTypeScriptApi\Translator;

abstract class AbstractTemporalField extends Field {
    private ?string $min_value;
    private ?string $max_value;

    /** @param array<string, mixed> $config */
    public function __construct(array $config = []) {
        parent::__construct($config);
        $this->min_value = $config['min_value'] ?? null;
        $this->max_value = $config['max_value'] ?? null;
    }

    public function getMinValue(): ?string {
        return $this->min_value;
    }

    public function getMaxValue(): ?string {
        return $this->max_value;
    }

    protected function validate(mixed $value): Fields\ValidationResult {
        $validation_result = parent::validate($value);
        if ($value !== null) { // The null case has been handled by the parent.
            if (!is_string($value)) {
                $validation_result->recordError(Translator::__('fields.must_be_string'));
            } else {
                $regex = $this->getRegex();
                if (!preg_match($regex, $value)) {
                    $validation_result->recordError(Translator::__(
                        'fields.must_match_regex',
                        ['regex' => $regex]
                    ));
                }
            }
        }
        if ($this->min_value !== null) {
            if ($value < $this->min_value) {
                $validation_result->recordError(Translator::__(
                    'fields.must_not_be_smaller',
                    ['min_value' => $this->min_value]
                ));
            }
        }
        if ($this->max_value !== null) {
            if ($value > $this->max_value) {
                $validation_result->recordError(Translator::__(
                    'fields.must_not_be_larger',
                    ['max_value' => $this->max_value]
                ));
            }
        }
        return $validation_result;
    }

    /** @param array<string, mixed> $config */
    public function getTypeScriptType(array $config = []): string {
        $should_substitute = $config['should_substitute'] ?? true;
        if ($this->export_as !== null && $should_substitute) {
            return $this->export_as;
        }
        return $this->getAllowNull() ? 'string|null' : 'string';
    }

    abstract protected function getRegex(): string;
}
