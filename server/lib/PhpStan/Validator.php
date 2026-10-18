<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PhpTypeScriptApi\Translator;

final class Validator {
    private function __construct(
        private PhpStanUtils $phpStanUtils,
        private ValidationContext $context,
    ) {
    }

    /** @param array<string, TypeNode> $aliasNodes */
    public static function validateSerialize(
        PhpStanUtils $phpStanUtils,
        mixed $value,
        TypeNode $type,
        array $aliasNodes = [],
    ): ValidationResultNode {
        return self::validate(
            $phpStanUtils,
            $value,
            $type,
            new ValidationContext($aliasNodes, ValidationDirection::Serialize),
        );
    }

    /** @param array<string, TypeNode> $aliasNodes */
    public static function validateDeserialize(
        PhpStanUtils $phpStanUtils,
        mixed $value,
        TypeNode $type,
        array $aliasNodes = [],
    ): ValidationResultNode {
        return self::validate(
            $phpStanUtils,
            $value,
            $type,
            new ValidationContext($aliasNodes, ValidationDirection::Deserialize),
        );
    }

    private static function validate(
        PhpStanUtils $phpStanUtils,
        mixed $value,
        TypeNode $type,
        ValidationContext $context,
    ): ValidationResultNode {
        $validator = new self($phpStanUtils, $context);
        return $validator->validateNode($value, $type);
    }

    private function validateNode(mixed $value, Node $node): ValidationResultNode {
        if ($node instanceof ConstTypeNode) {
            if ($node->constExpr instanceof ConstExprIntegerNode) {
                if (!is_int($value) || $value !== intval($node->constExpr->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                return ValidationResultNode::valid($value);
            }
            if ($node->constExpr instanceof ConstExprStringNode) {
                if (!is_string($value) || $value !== strval($node->constExpr->value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                return ValidationResultNode::valid($value);
            }
            throw new \Exception("Unknown ConstTypeNode->constExpr {$this->phpStanUtils->prettyNode($node->constExpr)}");
        }
        if ($node instanceof IdentifierTypeNode) {
            $mapping = [
                'mixed' => fn ($value): mixed => $value,
                'null' => fn ($value) => $value === null ? $value : throw new \Exception(),
                // Boolean
                'bool' => fn ($value): bool => is_bool($value) ? $value : throw new \Exception(),
                'boolean' => fn ($value): bool => is_bool($value) ? $value : throw new \Exception(),
                'true' => fn ($value): bool => (is_bool($value) && $value === true) ? $value : throw new \Exception(),
                'false' => fn ($value): bool => (is_bool($value) && $value === false) ? $value : throw new \Exception(),
                // Numeric
                'int' => fn ($value): int => is_int($value) ? $value : throw new \Exception(),
                'integer' => fn ($value): int => is_int($value) ? $value : throw new \Exception(),
                'positive-int' => fn ($value): int => (is_int($value) && $value > 0) ? $value : throw new \Exception(),
                'negative-int' => fn ($value): int => (is_int($value) && $value < 0) ? $value : throw new \Exception(),
                'non-positive-int' => fn ($value): int => (is_int($value) && $value <= 0) ? $value : throw new \Exception(),
                'non-negative-int' => fn ($value): int => (is_int($value) && $value >= 0) ? $value : throw new \Exception(),
                'non-zero-int' => fn ($value): int => (is_int($value) && $value !== 0) ? $value : throw new \Exception(),
                'float' => fn ($value): float => (is_float($value) || is_int($value)) ? floatval($value) : throw new \Exception(),
                'double' => fn ($value): float => (is_float($value) || is_int($value)) ? floatval($value) : throw new \Exception(),
                'number' => fn ($value): string|float|int => is_numeric($value) ? $value : throw new \Exception(),
                // String
                'string' => fn ($value): string => is_string($value) ? $value : throw new \Exception(),
                'numeric-string' => fn ($value): string => (is_string($value) && is_numeric($value)) ? $value : throw new \Exception(),
                'non-empty-string' => fn ($value): string => (is_string($value) && !empty($value)) ? $value : throw new \Exception(),
                'lowercase-string' => fn ($value): string => (is_string($value) && preg_match('/^[a-z]+$/', $value)) ? $value : throw new \Exception(),
                // Never
                'never' => fn ($value) => throw new \Exception(),
                'never-return' => fn ($value) => throw new \Exception(),
                'never-returns' => fn ($value) => throw new \Exception(),
                'no-return' => fn ($value) => throw new \Exception(),
            ];
            $fn = $mapping[$node->name] ?? null;
            if ($fn === null) {
                $aliased_node = $this->context->aliasNodes[$node->name] ?? null;
                if ($aliased_node) {
                    return $this->validateNode($value, $aliased_node);
                }
                $serialized_node = $this->phpStanUtils->getApiObjectTypeNode($node->name);
                if ($serialized_node) {
                    $class = $node->name;
                    if (!is_subclass_of($class, ApiObjectInterface::class)) {
                        throw new \Exception("Validator: Invalid ApiObjectInterface: {$class}");
                    }
                    if ($class && $value instanceof $class) {
                        $data = $value->toWire();
                        $result = $this->validateNode($data, $serialized_node);
                        if ($this->context->direction === ValidationDirection::Serialize) {
                            $result->setValue($data);
                        }
                        return $result;
                    }
                    $result = $this->validateNode($value, $serialized_node);
                    if ($this->context->direction === ValidationDirection::Deserialize) {
                        try {
                            $result->setValue($class::fromWire($value));
                        } catch (\Throwable $th) {
                            return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                        }
                    }
                    return $result;
                }
                throw new \Exception("Unknown IdentifierTypeNode name: {$node->name}");
            }
            try {
                return ValidationResultNode::valid($fn($value));
            } catch (\Throwable $th) {
                return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
            }
        }
        if ($node instanceof GenericTypeNode) {
            if ("{$node->type}" === 'int') {
                if (!is_int($value)) {
                    return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
                }
                if (count($node->genericTypes) !== 2) {
                    throw new \Exception("{$this->phpStanUtils->prettyNode($node)} must have two generic types");
                }
                $lower = $node->genericTypes[0];
                if ($lower instanceof ConstTypeNode) {
                    if (!$lower->constExpr instanceof ConstExprIntegerNode) {
                        throw new \Exception("Unsupported lower constExpr {$this->phpStanUtils->prettyNode($lower->constExpr)}");
                    }
                    if ($value < intval($lower->constExpr->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_smaller', ['min_value' => "{$lower}"]));
                    }
                } elseif ($lower instanceof IdentifierTypeNode) {
                    if ($lower->name !== 'min') {
                        throw new \Exception("Unsupported lower IdentifierTypeNode {$this->phpStanUtils->prettyNode($lower)}");
                    }
                } else {
                    throw new \Exception("Unsupported lower type {$this->phpStanUtils->prettyNode($lower)}");
                }
                $upper = $node->genericTypes[1];
                if ($upper instanceof ConstTypeNode) {
                    if (!$upper->constExpr instanceof ConstExprIntegerNode) {
                        throw new \Exception("Unsupported upper constExpr {$this->phpStanUtils->prettyNode($upper->constExpr)}");
                    }
                    if ($value > intval($upper->constExpr->value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_larger', ['max_value' => "{$upper}"]));
                    }
                } elseif ($upper instanceof IdentifierTypeNode) {
                    if ($upper->name !== 'max') {
                        throw new \Exception("Unsupported upper IdentifierTypeNode {$this->phpStanUtils->prettyNode($upper)}");
                    }
                } else {
                    throw new \Exception("Unsupported upper type {$this->phpStanUtils->prettyNode($upper)}");
                }
                return ValidationResultNode::valid($value);
            }
            if ("{$node->type}" === 'array' || "{$node->type}" === 'non-empty-array') {
                if (count($node->genericTypes) === 1) {
                    if (!is_array($value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
                    }
                    if ("{$node->type}" === 'non-empty-array' && empty($value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_empty', []));
                    }
                    if (!array_is_list($value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
                    }
                    $result_node = new ValidationResultNode();
                    $validated_value = [];
                    $item_type = $node->genericTypes[0];
                    foreach ($value as $key => $item) {
                        $item_node = $this->validateNode($item, $item_type);
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
                    if (!is_array($value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_be_dict', []));
                    }
                    if ("{$node->type}" === 'non-empty-array' && empty($value)) {
                        return ValidationResultNode::error(Translator::__('fields.must_not_be_empty', []));
                    }
                    $result_node = new ValidationResultNode();
                    $validated_value = [];
                    $key_type = $node->genericTypes[0];
                    $value_type = $node->genericTypes[1];
                    foreach ($value as $key => $item_value) {
                        $key_node = $this->validateNode($key, $key_type);
                        if (!$key_node->isValid()) {
                            $result_node->recordErrorInKey("{$key}", $key_node->getErrors());
                        }

                        $value_node = $this->validateNode($item_value, $value_type);
                        if (!$value_node->isValid()) {
                            $result_node->recordErrorInKey("{$key}", $value_node->getErrors());
                        } elseif ($key_node->getValue() === null) {
                            $result_node->recordErrorInKey("{$key}", Translator::__('fields.must_not_be_empty', []));
                        } else {
                            $validated_value[$key_node->getValue()] = $value_node->getValue();
                        }
                    }
                    $result_node->setValue($validated_value);
                    return $result_node;
                }
                throw new \Exception("{$this->phpStanUtils->prettyNode($node)} must have one or two generic types");
            }
            throw new \Exception("Unknown GenericTypeNode {$node}");
        }
        if ($node instanceof ObjectShapeNode || $node instanceof ArrayShapeNode) {
            if (!is_array($value)) {
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
                    if (!array_key_exists($key, $value)) {
                        if (!$item->optional) {
                            $result_node->recordErrorInKey("{$key}", Translator::__(
                                'fields.missing_key',
                                ['key' => $key]
                            ));
                        }
                        continue;
                    }
                    $item_value = $value[$key];
                    $value_node = $this->validateNode($item_value, $item->valueType);
                    if (!$value_node->isValid()) {
                        $result_node->recordErrorInKey("{$key}", $value_node->getErrors());
                    }
                    $validated_value[$key] = $value_node->getValue();
                } else {
                    throw new \Exception("Object key must be ConstExprStringNode, not {$this->phpStanUtils->prettyNode($item->keyName)}");
                }
            }
            $result_node->setValue($validated_value);
            foreach ($value as $key => $ignored) {
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
                $option_node = $this->validateNode($value, $node);
                if ($option_node->isValid()) {
                    return ValidationResultNode::valid($value);
                }
                $result_node->recordError($option_node->getErrors());
            }
            return $result_node;
        }
        if ($node instanceof NullableTypeNode) {
            if ($value === null) {
                return ValidationResultNode::valid($value);
            }
            return $this->validateNode($value, $node->type);
        }
        throw new \Exception("validateNode: Unknown node class: {$this->phpStanUtils->prettyNode($node)}");
    }
}
