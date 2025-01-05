<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class ValidationResultNode implements TypeNode, ConstExprNode {
    use NodeAttributes;

    /** @var array<string, array<array<mixed>|string>> */
    protected array $errors = [];

    protected mixed $value = null;

    /** @param string|array<string, array<array<mixed>|string>> $message */
    public function recordError(array|string $message): void {
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

    public function getValue(): mixed {
        return $this->value;
    }

    public function setValue(mixed $new_value): void {
        $this->value = $new_value;
    }

    public function isValid(): bool {
        return empty($this->errors);
    }

    public function __toString(): string {
        $json_value = json_encode($this->value);
        $json_errors = json_encode($this->getErrors());
        return $this->isValid()
            ? (is_bool($json_value) ? '🛑' : "✅ {$json_value}")
            : (is_bool($json_errors) ? '🛑' : "🚫 {$json_errors}");
    }

    public static function valid(mixed $value): self {
        $instance = new self();
        $instance->value = $value;
        return $instance;
    }

    public static function error(string $error): self {
        $instance = new self();
        $instance->recordError($error);
        return $instance;
    }
}
