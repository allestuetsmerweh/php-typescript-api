<?php

namespace PhpTypeScriptApi\Fields;

use PhpTypeScriptApi\HttpError;

/**
 * @phpstan-import-type ErrorsByField from HttpError
 */
class ValidationResult {
    /** @var ErrorsByField */
    public array $errors = [];

    public function recordError(string $message): void {
        $this->recordErrorInKey('.', $message);
    }

    /** @param string|ErrorsByField $message */
    public function recordErrorInKey(string $key, array|string $message): void {
        if (is_array($message)) {
            foreach ($message as $sub_key => $sub_errors) {
                $new_key = $sub_key === '.' ? $key : "{$key}.{$sub_key}";
                $this->errors[$new_key] ??= [];
                $this->errors[$new_key] = [
                    ...$this->errors[$new_key],
                    ...$sub_errors,
                ];
            }
            return;
        }
        $errors = $this->errors[$key] ?? [];
        $errors[] = $message;
        $this->errors[$key] = $errors;
    }

    /** @return ErrorsByField */
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
