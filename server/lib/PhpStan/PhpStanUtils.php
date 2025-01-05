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
        foreach ($php_doc_node->getImplementsTagValues() as $node) {
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

    public static function parseDocComment(string|false|null $doc_comment): PhpDocNode {
        if (!$doc_comment) {
            throw new \Exception("Cannot parse doc comment.");
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
