<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Attribute;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

final class TypeScriptVisitor extends AbstractNodeVisitor {
    /** @var array<string, TypeNode> */
    public array $exported_classes = [];

    /** @param array<string, TypeNode> $aliasNodes */
    public function __construct(
        protected PhpStanUtils $phpStanUtils,
        protected array $aliasNodes = [],
    ) {
    }

    public function enterNode(Node $originalNode): Node {
        $node = clone $originalNode;
        $node->setAttribute(Attribute::ORIGINAL_NODE, $originalNode);
        if ($node instanceof GenericTypeNode) {
            if ("{$node->type}" === 'int') {
                return $node->type;
            }
            $num_generic_types = count($node->genericTypes);
            if (
                ("{$node->type}" === 'array' || "{$node->type}" === 'non-empty-array')
                && $num_generic_types === 2
            ) {
                return new TypeScriptDictTypeNode($node->genericTypes[0], $node->genericTypes[1]);
            }
        }
        if ($node instanceof ArrayShapeNode || $node instanceof ObjectShapeNode) {
            foreach ($node->items as $item) {
                if ($item->keyName instanceof IdentifierTypeNode) {
                    $item->keyName = new ConstExprStringNode($item->keyName->name, ConstExprStringNode::SINGLE_QUOTED);
                }
            }
        }
        return $node;
    }

    public function leaveNode(Node $originalNode): Node {
        $node = clone $originalNode;
        $node->setAttribute(Attribute::ORIGINAL_NODE, $originalNode);
        if ($node instanceof ConstTypeNode || $node instanceof ConstExprStringNode || $node instanceof ConstExprIntegerNode) {
            // Do not modify $node in this case
        } elseif ($node instanceof IdentifierTypeNode) {
            $mapping = [
                'mixed' => 'unknown',
                'null' => 'null',
                // Boolean
                'bool' => 'boolean',
                'boolean' => 'boolean',
                'true' => 'true',
                'false' => 'false',
                // Numeric
                'int' => 'number',
                'integer' => 'number',
                'positive-int' => 'number',
                'negative-int' => 'number',
                'non-positive-int' => 'number',
                'non-negative-int' => 'number',
                'non-zero-int' => 'number',
                'float' => 'number',
                'double' => 'number',
                'number' => 'number',
                // String
                'string' => 'string',
                'numeric-string' => 'string',
                'non-empty-string' => 'string',
                'lowercase-string' => 'string',
                // Array
                'array' => 'Array',
                'non-empty-array' => 'Array',
                'object' => 'Array',
                // Never
                'never' => 'never',
                'never-return' => 'never',
                'never-returns' => 'never',
                'no-return' => 'never',
            ];
            $new_name = $mapping[$node->name] ?? null;
            if ($new_name === null) {
                $resolved_node = $this->aliasNodes[$node->name] ??
                    $this->phpStanUtils->getApiObjectTypeNode($node->name);
                if ($resolved_node) {
                    $sane_node = preg_replace('/[^A-Za-z0-9_]/', '_', $node->name);
                    $this->exported_classes[$sane_node] = $resolved_node;
                    $new_name = $sane_node;
                }
            }
            if ($new_name === null) {
                throw new \Exception("Unknown IdentifierTypeNode name: {$node->name}");
            }
            $node->name = $new_name;
        } elseif ($node instanceof GenericTypeNode) {
            // Do not modify $node in this case
        } elseif ($node instanceof ArrayShapeNode || $node instanceof ObjectShapeNode) {
            return new TypeScriptObjectTypeNode($node->items);
        } elseif ($node instanceof ArrayShapeItemNode || $node instanceof ObjectShapeItemNode) {
            // Do not modify $node in this case
        } elseif ($node instanceof TypeScriptDictTypeNode) {
            // Do not modify $node in this case
        } elseif ($node instanceof UnionTypeNode) {
            // Do not modify $node in this case
        } elseif ($node instanceof NullableTypeNode) {
            return new UnionTypeNode([$node->type, new IdentifierTypeNode('null')]);
        } else {
            $node_class_name = get_class($node);
            throw new \Exception("leaveNode: Unknown node class: {$node_class_name}");
        }
        return $node;
    }
}
