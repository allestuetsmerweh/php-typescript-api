<?php

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

class PhpStanUtils {
    /** @var ?array<string, string> */
    protected static ?array $registry = [];

    public static function getApiObjectTypeNode(string $name): ?TypeNode {
        $class_info = self::resolveApiObjectClass($name);
        if ($class_info === null) {
            return null;
        }
        $php_doc_node = self::parseDocComment($class_info->getDocComment());
        $implements_node = null;
        foreach ($php_doc_node?->getImplementsTagValues() ?? [] as $node) {
            $generic_node = $node->type;
            if ("{$generic_node->type}" === 'ApiObjectInterface') {
                $implements_node = $generic_node->genericTypes[0];
            }
        }
        return $implements_node;
    }

    /** @return \ReflectionClass<ApiObjectInterface<mixed>> */
    public static function resolveApiObjectClass(string $name): ?\ReflectionClass {
        try {
            // @phpstan-ignore argument.type
            $class_info = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            $full_name = PhpStanUtils::$registry[$name] ?? '';
            try {
                // @phpstan-ignore argument.type
                $class_info = new \ReflectionClass($full_name);
            } catch (\ReflectionException) {
                return null;
            }
        }
        if (!$class_info->implementsInterface(ApiObjectInterface::class)) {
            return null;
        }
        // @phpstan-ignore return.type
        return $class_info;
    }

    /** @param class-string<ApiObjectInterface<mixed>> $class */
    public static function registerApiObject(string $class): void {
        $class_info = new \ReflectionClass($class);
        if ($class_info->implementsInterface(ApiObjectInterface::class)) {
            $components = explode('\\', $class);
            $short_name = end($components);
            PhpStanUtils::$registry[$short_name] = $class;
        }
    }

    /** @return array<string, TypeNode> */
    public static function getAliases(?PhpDocNode $php_doc_node): array {
        $aliases = [];
        foreach ($php_doc_node?->getTypeAliasTagValues() ?? [] as $alias_node) {
            $aliases[$alias_node->alias] = $alias_node->type;
        }
        foreach ($php_doc_node?->getTypeAliasImportTagValues() ?? [] as $import_node) {
            $alias = $import_node->importedAs ?? $import_node->importedAlias;
            $from = $import_node->importedFrom->name;
            $full_class_name = '';
            foreach (\get_declared_classes() as $class_name) {
                $components = explode('\\', $class_name);
                $short_name = end($components);
                if ($from === $class_name || $from === $short_name) {
                    $full_class_name = $class_name;
                }
            }
            // Too dynamic for phpstan...
            // @phpstan-ignore argument.type
            $class_info = new \ReflectionClass($full_class_name);
            $import_php_doc_node = self::parseDocComment($class_info->getDocComment());
            $found_alias = null;
            foreach ($import_php_doc_node?->getTypeAliasTagValues() ?? [] as $alias_node) {
                if ($alias_node->alias === $import_node->importedAlias) {
                    $found_alias = $alias_node;
                }
            }
            if ($found_alias === null) {
                throw new \Exception("Failed importing {$import_node->importedAlias} from {$from}");
            }
            $aliases[$alias] = $found_alias->type;
        }
        return $aliases;
    }

    public static function parseDocComment(string|false|null $doc_comment): ?PhpDocNode {
        if (!$doc_comment) {
            return null;
        }
        $config = new ParserConfig(usedAttributes: []);
        $lexer = new Lexer($config);
        $constExprParser = new ConstExprParser($config);
        $typeParser = new TypeParser($config, $constExprParser);
        $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);
        $tokens = new TokenIterator($lexer->tokenize($doc_comment));
        return $phpDocParser->parse($tokens);
    }
}
