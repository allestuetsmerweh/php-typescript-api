<?php

namespace PhpTypeScriptApi\Fields;

class ValidationResult {
    /** @var array<string, array<array<mixed>|string>> */
    public array $errors = [];

    public function recordError(string $message): void {
        $this->recordErrorInKey('.', $message);
    }

    /** @param string|array<string, array<array<mixed>|string>> $message */
    public function recordErrorInKey(string $key, array|string $message): void {
        $errors = $this->errors[$key] ?? [];
        $errors[] = $message;
        $this->errors[$key] = $errors;
    }

    /** @return array<string, array<array<mixed>|string>> */
    public function getErrors(): array {
        return $this->errors;
    }

    public function isValid(): bool {
        return empty($this->errors);
    }

    public static function create(): self {
        return new self();
    }
}
