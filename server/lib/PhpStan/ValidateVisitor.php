<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PhpTypeScriptApi\Translator;

final class ValidateVisitor extends AbstractNodeVisitor {
    private PhpStanUtils $phpStanUtils;

    /** @param \ReflectionClass<object> $endpointClass */
    public function __construct(
        protected \ReflectionClass $endpointClass,
        protected mixed $value,
    ) {
        $this->phpStanUtils = new PhpStanUtils();
    }

    /** @param \ReflectionClass<object> $endpointClass */
    public static function validate(\ReflectionClass $endpointClass, mixed $value, Node $type): ValidationResultNode {
        $validator = new ValidateVisitor($endpointClass, $value);
        return $validator->subValidate($value, $type);
    }

    public function enterNode(Node $node): ValidationResultNode {
        if ($node instanceof ConstTypeNode) {
            if ($node->constExpr instanceof ConstExprIntegerNode) {
                if (!is_int($this->value) || $this->value !== intval($node->constExpr->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                return ValidationResultNode::valid();
            }
            if ($node->constExpr instanceof ConstExprStringNode) {
                if (!is_string($this->value) || $this->value !== strval($node->constExpr->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                return ValidationResultNode::valid();
            }
            throw new \Exception("Unknown ConstTypeNode->constExpr {$this->prettyNode($node->constExpr)}");
        }
        if ($node instanceof IdentifierTypeNode) {
            $mapping = [
                'mixed' => fn (): bool => true,
                'null' => fn ($value): bool => $value === null,
                // Boolean
                'bool' => fn ($value): bool => is_bool($value),
                'boolean' => fn ($value): bool => is_bool($value),
                // Numeric
                'int' => fn ($value): bool => is_int($value),
                'integer' => fn ($value): bool => is_int($value),
                'positive-int' => fn ($value): bool => is_int($value) && $value > 0,
                'negative-int' => fn ($value): bool => is_int($value) && $value < 0,
                'non-positive-int' => fn ($value): bool => is_int($value) && $value <= 0,
                'non-negative-int' => fn ($value): bool => is_int($value) && $value >= 0,
                'non-zero-int' => fn ($value): bool => is_int($value) && $value !== 0,
                'float' => fn ($value): bool => is_float($value),
                'double' => fn ($value): bool => is_double($value),
                'number' => fn ($value): bool => is_numeric($value),
                // String
                'string' => fn ($value): bool => is_string($value),
                'numeric-string' => fn ($value): bool => is_string($value) && is_numeric($value),
                'non-empty-string' => fn ($value): bool => is_string($value) && !empty($value),
                'lowercase-string' => fn ($value): bool => is_string($value) && preg_match('/^[a-z]+$/', $value),
            ];
            $fn = $mapping[$node->name] ?? null;
            if ($fn === null) {
                if (preg_match('/^[A-Z]/', $node->name)) {
                    $named_class_type = $this->phpStanUtils->getAliasTypeNode($node->name, $this->endpointClass);
                    return $this->subValidate($this->value, $named_class_type);
                }
                throw new \Exception("Unknown IdentifierTypeNode name: {$node->name}");
            }
            if (!$fn($this->value)) {
                return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
            }
            return ValidationResultNode::valid();
        }
        if ($node instanceof GenericTypeNode) {
            if ("{$node->type}" === 'int') {
                if (!is_int($this->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                if (count($node->genericTypes) !== 2) {
                    throw new \Exception("{$this->prettyNode($node)} must have two generic types");
                }
                $lower = $node->genericTypes[0];
                if ($lower instanceof ConstTypeNode) {
                    if (!($lower->constExpr instanceof ConstExprIntegerNode)) {
                        throw new \Exception("Unsupported lower constExpr {$this->prettyNode($lower->constExpr)}");
                    }
                    if ($this->value < intval($lower->constExpr->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_smaller', ['min_value' => "{$lower}"]));
                    }
                } elseif ($lower instanceof IdentifierTypeNode) {
                    if ($lower->name !== 'min') {
                        throw new \Exception("Unsupported lower IdentifierTypeNode {$this->prettyNode($lower)}");
                    }
                } else {
                    throw new \Exception("Unsupported lower type {$this->prettyNode($lower)}");
                }
                $upper = $node->genericTypes[1];
                if ($upper instanceof ConstTypeNode) {
                    if (!($upper->constExpr instanceof ConstExprIntegerNode)) {
                        throw new \Exception("Unsupported upper constExpr {$this->prettyNode($upper->constExpr)}");
                    }
                    if ($this->value > intval($upper->constExpr->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_larger', ['max_value' => "{$upper}"]));
                    }
                } elseif ($upper instanceof IdentifierTypeNode) {
                    if ($upper->name !== 'max') {
                        throw new \Exception("Unsupported upper IdentifierTypeNode {$this->prettyNode($upper)}");
                    }
                } else {
                    throw new \Exception("Unsupported upper type {$this->prettyNode($upper)}");
                }
                return ValidationResultNode::valid();
            }
            if ("{$node->type}" === 'array' || "{$node->type}" === 'non-empty-array') {
                if (!is_array($this->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
                }
                if ("{$node->type}" === 'non-empty-array' && empty($this->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_not_be_empty', []));
                }
                if (count($node->genericTypes) === 1) {
                    if (!array_is_list($this->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
                    }
                    $result_node = new ValidationResultNode();
                    $item_type = $node->genericTypes[0];
                    foreach ($this->value as $key => $item) {
                        $item_node = $this->subValidate($item, $item_type);
                        if (!$item_node->isValid()) {
                            $result_node->recordErrorInKey("{$key}", $item_node->getErrors());
                        }
                    }
                    return $result_node;
                }
                if (count($node->genericTypes) === 2) {
                    $result_node = new ValidationResultNode();
                    $key_type = $node->genericTypes[0];
                    $value_type = $node->genericTypes[1];
                    foreach ($this->value as $key => $value) {
                        $key_node = $this->subValidate($key, $key_type);
                        if (!$key_node->isValid()) {
                            $result_node->recordErrorInKey("{$key}", $key_node->getErrors());
                        }

                        $value_node = $this->subValidate($value, $value_type);
                        if (!$value_node->isValid()) {
                            $result_node->recordErrorInKey("{$key}", $value_node->getErrors());
                        }
                    }
                    return $result_node;
                }
                throw new \Exception("{$this->prettyNode($node)} must have one or two generic types");
            }
            throw new \Exception("Unknown GenericTypeNode {$node}");
        }
        if ($node instanceof ObjectShapeNode || $node instanceof ArrayShapeNode) {
            if (!is_array($this->value)) {
                return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
            }
            $result_node = new ValidationResultNode();
            $is_valid_key = [];
            foreach ($node->items as $item) {
                if (
                    $item->keyName instanceof ConstExprStringNode
                    || $item->keyName instanceof ConstExprIntegerNode
                    || $item->keyName instanceof IdentifierTypeNode
                ) {
                    $key = $item->keyName instanceof IdentifierTypeNode
                        ? $item->keyName->name
                        : $item->keyName->value;
                    $is_valid_key[$key] = true;
                    if (!array_key_exists($key, $this->value)) {
                        if (!$item->optional) {
                            $result_node->recordErrorInKey("{$key}", Translator::__(
                                'fields.missing_key',
                                ['key' => $key]
                            ));
                        }
                        continue;
                    }
                    $value = $this->value[$key];
                    $value_node = $this->subValidate($value, $item->valueType);
                    if (!$value_node->isValid()) {
                        $result_node->recordErrorInKey("{$key}", $value_node->getErrors());
                    }
                } else {
                    throw new \Exception("Object key must be ConstExprStringNode, not {$this->prettyNode($item->keyName)}");
                }
            }
            foreach ($this->value as $key => $value) {
                if (!($is_valid_key[$key] ?? false)) {
                    $result_node->recordErrorInKey("{$key}", Translator::__(
                        'fields.unknown_key',
                        ['key' => $key]
                    ));
                }
            }
            return $result_node;
        }
        if ($node instanceof UnionTypeNode) {
            $result_node = new ValidationResultNode();
            foreach ($node->types as $node) {
                $option_node = $this->subValidate($this->value, $node);
                if ($option_node->isValid()) {
                    return ValidationResultNode::valid();
                }
                $result_node->recordError($option_node->getErrors());
            }
            return $result_node;
        }
        if ($node instanceof NullableTypeNode) {
            if ($this->value === null) {
                return ValidationResultNode::valid();
            }
            return $this->subValidate($this->value, $node->type);
        }
        throw new \Exception("enterNode: Unknown node class: {$this->prettyNode($node)}");
    }

    public function subValidate(mixed $value, Node $type): ValidationResultNode {
        $visitor = new ValidateVisitor($this->endpointClass, $value);
        $traverser = new NodeTraverser([$visitor]);
        [$result_node] = $traverser->traverse([$type]);
        if (!($result_node instanceof ValidationResultNode)) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception("Validation result for {$this->prettyNode($type)} must be ValidationResultNode, not {$this->prettyNode($result_node)}");
            // @codeCoverageIgnoreEnd
        }
        return $result_node;
    }

    protected function prettyNode(?Node $node): string {
        if ($node === null) {
            return 'null';
        }
        $class_components = explode('\\', get_class($node));
        $class_basename = $class_components[count($class_components) - 1];
        return "{$node} ({$class_basename})";
    }
}
