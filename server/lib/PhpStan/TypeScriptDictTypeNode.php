<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class TypeScriptDictTypeNode implements TypeNode {
    use NodeAttributes;

    public function __construct(
        public TypeNode $indexType,
        public TypeNode $valueType,
    ) {
    }

    public function __toString(): string {
        return "{[key: {$this->indexType}]: {$this->valueType}}";
    }
}
