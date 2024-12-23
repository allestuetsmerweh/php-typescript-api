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

    public function isValid(): bool {
        return empty($this->errors);
    }

    public function __toString(): string {
        $json = json_encode($this->getErrors());
        return $this->isValid() ? '✅' : (is_bool($json) ? '🛑' : $json);
    }

    public static function valid(): self {
        return new self();
    }

    public static function error(string $error): self {
        $instance = new self();
        $instance->recordError($error);
        return $instance;
    }
}
