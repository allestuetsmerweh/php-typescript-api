<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;

/**
 * @phpstan-import-type NamespaceAliases from PhpStanUtils
 */
final class ReplaceNodesVisitor extends AbstractNodeVisitor {
    /** @param callable(Node): Node $replaceFn */
    public function __construct(
        protected PhpStanUtils $phpStanUtils,
        protected mixed $replaceFn,
    ) {
    }

    public function enterNode(Node $originalNode): Node {
        $fn = $this->replaceFn;
        return $fn(clone $originalNode);
    }
}
