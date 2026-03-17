<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;

/**
 * @phpstan-import-type NamespaceAliases from PhpStanUtils
 */
final class ResolveAliasesVisitor extends AbstractNodeVisitor {
    /** @param NamespaceAliases $aliasNodes */
    public function __construct(
        protected PhpStanUtils $phpStanUtils,
        protected array $aliasNodes = [],
    ) {
    }

    public function enterNode(Node $originalNode): Node {
        $node = clone $originalNode;
        if (
            $node instanceof IdentifierTypeNode
            && isset($this->aliasNodes[$node->name])
        ) {
            return $this->phpStanUtils->resolveAlias($this->aliasNodes[$node->name]);
        }
        return $node;
    }
}
