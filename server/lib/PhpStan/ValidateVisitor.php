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
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PhpTypeScriptApi\Translator;

final class ValidateVisitor extends AbstractNodeVisitor {
    /** @param array<string, TypeNode> $aliasNodes */
    public function __construct(
        protected PhpStanUtils $phpStanUtils,
        protected mixed $value,
        protected array $aliasNodes = [],
        protected bool $serialize = false,
    ) {
    }

    /** @param array<string, TypeNode> $aliasNodes */
    public static function validateSerialize(
        PhpStanUtils $phpStanUtils,
        mixed $value,
        Node $type,
        array $aliasNodes = [],
    ): ValidationResultNode {
        $validator = new ValidateVisitor($phpStanUtils, $value, $aliasNodes, true);
        return $validator->subValidate($value, $type);
    }

    /** @param array<string, TypeNode> $aliasNodes */
    public static function validateDeserialize(
        PhpStanUtils $phpStanUtils,
        mixed $value,
        Node $type,
        array $aliasNodes = [],
    ): ValidationResultNode {
        $validator = new ValidateVisitor($phpStanUtils, $value, $aliasNodes, false);
        return $validator->subValidate($value, $type);
    }

    public function enterNode(Node $node): ValidationResultNode {
        if ($node instanceof ConstTypeNode) {
            if ($node->constExpr instanceof ConstExprIntegerNode) {
                if (!is_int($this->value) || $this->value !== intval($node->constExpr->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                return ValidationResultNode::valid($this->value);
            }
            if ($node->constExpr instanceof ConstExprStringNode) {
                if (!is_string($this->value) || $this->value !== strval($node->constExpr->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                return ValidationResultNode::valid($this->value);
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
                'true' => fn ($value): bool => is_bool($value) && $value === true,
                'false' => fn ($value): bool => is_bool($value) && $value === false,
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
                // Never
                'never' => fn (): bool => false,
                'never-return' => fn (): bool => false,
                'never-returns' => fn (): bool => false,
                'no-return' => fn (): bool => false,
            ];
            $fn = $mapping[$node->name] ?? null;
            if ($fn === null) {
                $aliased_node = $this->aliasNodes[$node->name] ?? null;
                if ($aliased_node) {
                    return $this->subValidate($this->value, $aliased_node);
                }
                $serialized_node = $this->phpStanUtils->getApiObjectTypeNode($node->name);
                if ($serialized_node) {
                    $class_info = $this->phpStanUtils->resolveApiObjectClass($node->name);
                    $class = $class_info?->getName();
                    if ($this->value instanceof $class) {
                        // @phpstan-ignore method.notFound
                        $data = $this->value->data();
                        $result = $this->subValidate($data, $serialized_node);
                        if ($this->serialize) {
                            $result->setValue($data);
                        }
                        return $result;
                    }
                    $result = $this->subValidate($this->value, $serialized_node);
                    if (!$this->serialize) {
                        try {
                            // @phpstan-ignore staticMethod.nonObject
                            $result->setValue($class::fromData($this->value));
                        } catch (\Throwable $th) {
                            return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                        }
                    }
                    return $result;
                }
                throw new \Exception("Unknown IdentifierTypeNode name: {$node->name}");
            }
            if (!$fn($this->value)) {
                return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
            }
            return ValidationResultNode::valid($this->value);
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
                return ValidationResultNode::valid($this->value);
            }
            if ("{$node->type}" === 'array' || "{$node->type}" === 'non-empty-array') {
                if (count($node->genericTypes) === 1) {
                    if (!is_array($this->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
                    }
                    if ("{$node->type}" === 'non-empty-array' && empty($this->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_empty', []));
                    }
                    if (!array_is_list($this->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
                    }
                    $result_node = new ValidationResultNode();
                    $validated_value = [];
                    $item_type = $node->genericTypes[0];
                    foreach ($this->value as $key => $item) {
                        $item_node = $this->subValidate($item, $item_type);
                        if (!$item_node->isValid()) {
                            $result_node->recordErrorInKey("{$key}", $item_node->getErrors());
                        } else {
                            $validated_value[$key] = $item_node->getValue();
                        }
                    }
                    $result_node->setValue($validated_value);
                    return $result_node;
                }
                if (count($node->genericTypes) === 2) {
                    if (!is_array($this->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_be_dict', []));
                    }
                    if ("{$node->type}" === 'non-empty-array' && empty($this->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_empty', []));
                    }
                    $result_node = new ValidationResultNode();
                    $validated_value = [];
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
                        } else {
                            $validated_value[$key_node->getValue()] = $value_node->getValue();
                        }
                    }
                    $result_node->setValue($validated_value);
                    return $result_node;
                }
                throw new \Exception("{$this->prettyNode($node)} must have one or two generic types");
            }
            throw new \Exception("Unknown GenericTypeNode {$node}");
        }
        if ($node instanceof ObjectShapeNode || $node instanceof ArrayShapeNode) {
            if (!is_array($this->value)) {
                return ValidationResultNode::error(Translator::__('fields.must_be_object', []));
            }
            $result_node = new ValidationResultNode();
            $validated_value = [];
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
                    $validated_value[$key] = $value_node->getValue();
                } else {
                    throw new \Exception("Object key must be ConstExprStringNode, not {$this->prettyNode($item->keyName)}");
                }
            }
            $result_node->setValue($validated_value);
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
                    return ValidationResultNode::valid($this->value);
                }
                $result_node->recordError($option_node->getErrors());
            }
            return $result_node;
        }
        if ($node instanceof NullableTypeNode) {
            if ($this->value === null) {
                return ValidationResultNode::valid($this->value);
            }
            return $this->subValidate($this->value, $node->type);
        }
        throw new \Exception("enterNode: Unknown node class: {$this->prettyNode($node)}");
    }

    public function subValidate(mixed $value, Node $type): ValidationResultNode {
        $visitor = new ValidateVisitor($this->phpStanUtils, $value, $this->aliasNodes, $this->serialize);
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
