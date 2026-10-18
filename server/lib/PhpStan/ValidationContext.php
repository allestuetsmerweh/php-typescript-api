<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;

final class ValidationContext {
    /** @param array<string, TypeNode> $aliasNodes */
    public function __construct(
        public array $aliasNodes = [],
        public ValidationDirection $direction = ValidationDirection::Deserialize,
    ) {
    }
}
