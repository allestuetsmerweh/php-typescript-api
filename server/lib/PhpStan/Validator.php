<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
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
            return $this->validateConstTypeNode($value, $node);
        }
        if ($node instanceof IdentifierTypeNode) {
            return $this->validateIdentifierTypeNode($value, $node);
        }
        if ($node instanceof GenericTypeNode) {
            return $this->validateGenericTypeNode($value, $node);
        }
        if ($node instanceof ArrayTypeNode) {
            return $this->validateArrayTypeNode($value, $node);
        }
        if ($node instanceof ObjectShapeNode || $node instanceof ArrayShapeNode) {
            return $this->validateShapeTypeNode($value, $node);
        }
        if ($node instanceof UnionTypeNode) {
            return $this->validateUnionTypeNode($value, $node);
        }
        if ($node instanceof NullableTypeNode) {
            return $this->validateNullableTypeNode($value, $node);
        }
        throw new \Exception("validateNode: Unknown node class: {$this->phpStanUtils->prettyNode($node)}");
    }

    private function validateConstTypeNode(mixed $value, ConstTypeNode $node): ValidationResultNode {
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

    private function validateIdentifierTypeNode(mixed $value, IdentifierTypeNode $node): ValidationResultNode {
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
            'number' => fn ($value): int|float => (is_int($value) || is_float($value)) ? $value : throw new \Exception(),
            'numeric' => fn ($value): int|float|string => is_numeric($value) ? $value : throw new \Exception(),
            // Scalar
            'array-key' => fn ($value): int|string => (is_int($value) || is_string($value)) ? $value : throw new \Exception(),
            'scalar' => fn ($value): int|float|string|bool => (is_int($value) || is_float($value) || is_string($value) || is_bool($value)) ? $value : throw new \Exception(),
            // String
            'string' => fn ($value): string => is_string($value) ? $value : throw new \Exception(),
            'numeric-string' => fn ($value): string => (is_string($value) && is_numeric($value)) ? $value : throw new \Exception(),
            'non-empty-string' => fn ($value): string => (is_string($value) && $value !== '') ? $value : throw new \Exception(),
            'lowercase-string' => fn ($value): string => (is_string($value) && strtolower($value) === $value) ? $value : throw new \Exception(),
            'uppercase-string' => fn ($value): string => (is_string($value) && strtoupper($value) === $value) ? $value : throw new \Exception(),
            'truthy-string' => fn ($value): string => (is_string($value) && !empty($value)) ? $value : throw new \Exception(),
            'non-falsy-string' => fn ($value): string => (is_string($value) && !empty($value)) ? $value : throw new \Exception(),
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
                return $this->validateApiObject($value, $node->name, $serialized_node, $node);
            }
            throw new \Exception("Unknown IdentifierTypeNode name: {$node->name}");
        }
        try {
            return ValidationResultNode::valid($fn($value));
        } catch (\Throwable $th) {
            return ValidationResultNode::error(Translator::__('fields.must_be_type', ['type' => "{$node}"]));
        }
    }

    private function validateGenericTypeNode(mixed $value, GenericTypeNode $node): ValidationResultNode {
        if ("{$node->type}" === 'int') {
            return $this->validateIntRangeTypeNode($value, $node);
        }
        if ("{$node->type}" === 'array' || "{$node->type}" === 'non-empty-array') {
            return $this->validateArrayTypeNode($value, $node);
        }
        if ("{$node->type}" === 'list' || "{$node->type}" === 'non-empty-list') {
            return $this->validateListTypeNode($value, $node);
        }
        throw new \Exception("Unknown GenericTypeNode {$node}");
    }

    private function validateIntRangeTypeNode(mixed $value, GenericTypeNode $node): ValidationResultNode {
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

    private function validateArrayTypeNode(mixed $value, GenericTypeNode|ArrayTypeNode $node): ValidationResultNode {
        if ($node instanceof ArrayTypeNode) {
            if (!is_array($value)) {
                return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
            }
            return $this->validateArrayValues($value, $node->type);
        }
        $non_empty = "{$node->type}" === 'non-empty-array';
        if (count($node->genericTypes) === 1) {
            if (!is_array($value)) {
                return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
            }
            if ($non_empty && empty($value)) {
                return ValidationResultNode::error(Translator::__('fields.must_not_be_empty', []));
            }
            return $this->validateArrayValues($value, $node->genericTypes[0]);
        }
        if (count($node->genericTypes) === 2) {
            if (!is_array($value)) {
                return ValidationResultNode::error(Translator::__('fields.must_be_dict', []));
            }
            if ($non_empty && empty($value)) {
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
                } else {
                    $validated_key = $key_node->getValue();
                    if (is_int($validated_key) || is_string($validated_key)) {
                        $validated_value[$validated_key] = $value_node->getValue();
                    } else {
                        $result_node->recordErrorInKey("{$key}", Translator::__('fields.must_not_be_empty', []));
                    }
                }
            }
            $result_node->setValue($validated_value);
            return $result_node;
        }
        throw new \Exception("{$this->phpStanUtils->prettyNode($node)} must have one or two generic types");
    }

    private function validateListTypeNode(mixed $value, GenericTypeNode $node): ValidationResultNode {
        if (count($node->genericTypes) !== 1) {
            throw new \Exception("{$this->phpStanUtils->prettyNode($node)} must have one generic type");
        }
        if (!is_array($value)) {
            return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
        }
        if ("{$node->type}" === 'non-empty-list' && empty($value)) {
            return ValidationResultNode::error(Translator::__('fields.must_not_be_empty', []));
        }
        if (!array_is_list($value)) {
            return ValidationResultNode::error(Translator::__('fields.must_be_array', []));
        }
        return $this->validateArrayValues($value, $node->genericTypes[0]);
    }

    private function validateShapeTypeNode(mixed $value, ObjectShapeNode|ArrayShapeNode $node): ValidationResultNode {
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

    private function validateUnionTypeNode(mixed $value, UnionTypeNode $node): ValidationResultNode {
        $result_node = new ValidationResultNode();
        foreach ($node->types as $option_type) {
            $option_node = $this->validateNode($value, $option_type);
            if ($option_node->isValid()) {
                return ValidationResultNode::valid($value);
            }
            $result_node->recordError($option_node->getErrors());
        }
        return $result_node;
    }

    private function validateNullableTypeNode(mixed $value, NullableTypeNode $node): ValidationResultNode {
        if ($value === null) {
            return ValidationResultNode::valid($value);
        }
        return $this->validateNode($value, $node->type);
    }

    private function validateApiObject(mixed $value, string $class, TypeNode $serialized_node, Node $node): ValidationResultNode {
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

    /** @param array<mixed> $value */
    private function validateArrayValues(array $value, Node $item_type): ValidationResultNode {
        $result_node = new ValidationResultNode();
        $validated_value = [];
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
}
