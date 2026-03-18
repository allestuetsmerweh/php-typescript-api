<?php

namespace PhpTypeScriptApi\PhpStan;

/**
 * @template T
 *
 * @implements ApiObjectInterface<non-empty-string>
 */
class JsonEncoded implements ApiObjectInterface {
    /** @param non-empty-string $json_encoded_data */
    protected function __construct(
        protected string $json_encoded_data
    ) {
    }

    public function toWire(): mixed {
        return $this->json_encoded_data;
    }

    /** @return JsonEncoded<T> */
    public static function fromWire(mixed $wire): JsonEncoded {
        $class_name = get_called_class();
        if (!is_string($wire) || !$wire) {
            throw new \InvalidArgumentException("{$class_name} must be string");
        }
        $data = json_decode($wire, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("{$class_name} must be valid JSON");
        }
        $php_stan_utils = new PhpStanUtils();
        $generics = $php_stan_utils->getSuperGenerics($class_name, JsonEncoded::class);
        // $php_doc_node = $php_stan_utils->parseClassDocComment($class_name);
        // if (!$php_doc_node) {
        //     throw new \Exception("Could not parse type for {$class_name}");
        // }
        // $extends_node = $php_stan_utils->resolveTypeAliases(
        //     $php_doc_node->getExtendsTagValues()[0],
        //     $php_stan_utils->getAliases($php_doc_node),
        // );
        // if (!preg_match('/(^|\\\)JsonEncoded$/', "{$extends_node->type->type}")) {
        //     throw new \Exception("{$class_name} does not extend JsonEncoded");
        // }
        if (count($generics) !== 1) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            $pretty_generics = implode(', ', $generics);
            throw new \Exception("{$class_name} must provide one generic to JsonEncoded, provided JsonEncoded<{$pretty_generics}>");
            // @codeCoverageIgnoreEnd
        }
        $type_node = $generics[0];
        $result_node = ValidateVisitor::validateDeserialize(new PhpStanUtils(), $data, $type_node);
        if (!$result_node->isValid()) {
            throw new \InvalidArgumentException("{$class_name} must be valid {$type_node}");
        }
        return new self($wire);
    }

    /** @return T */
    public function toData(): mixed {
        return json_decode($this->json_encoded_data, true);
    }

    /**
     * @param T $data
     *
     * @return JsonEncoded<T>
     */
    public static function fromData(mixed $data): JsonEncoded {
        $json_encoded_data = json_encode($data);
        if (!$json_encoded_data) {
            $pretty_data = var_export($data, true);
            throw new \Exception("JsonEncoded::fromData with invalid data: {$pretty_data}");
        }
        return new JsonEncoded($json_encoded_data);
    }

    public function __toString(): string {
        return "{$this->json_encoded_data}";
    }
}
