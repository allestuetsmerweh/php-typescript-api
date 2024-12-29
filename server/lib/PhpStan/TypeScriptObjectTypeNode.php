<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\NodeAttributes;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

class TypeScriptObjectTypeNode implements TypeNode {
    use NodeAttributes;

    /** @param array<ArrayShapeItemNode|ObjectShapeItemNode> $items */
    public function __construct(
        protected array $items,
    ) {
    }

    public function __toString(): string {
        if (empty($this->items)) {
            return 'Record<string, never>';
        }
        return '{'.implode(', ', $this->items).'}';
    }
}
