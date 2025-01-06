<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

final class ResolveAliasesVisitor extends AbstractNodeVisitor {
    /** @param array<string, TypeNode> $aliasNodes */
    public function __construct(
        protected array $aliasNodes = [],
    ) {
    }

    public function enterNode(Node $originalNode): Node {
        $node = clone $originalNode;
        if (
            $node instanceof IdentifierTypeNode
            && isset($this->aliasNodes[$node->name])
        ) {
            return clone $this->aliasNodes[$node->name];
        }
        return $node;
    }
}
