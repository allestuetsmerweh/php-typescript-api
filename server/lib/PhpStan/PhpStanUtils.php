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
    /** @param \ReflectionClass<object> $class_info */
    public function getNamedTypeNode(\ReflectionClass $class_info): TypeNode {
        if (!$this->extendsNamedType($class_info)) {
            throw new \Exception('Only classes directly extending NamedType may be used.');
        }
        $phpDocNode = $this->parseDocComment($class_info->getDocComment());
        $php_type_node = $phpDocNode->getExtendsTagValues()[0]->type;
        if (!preg_match('/(^|\\\)NamedType$/', "{$php_type_node->type}")) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception('Only classes directly extending NamedType (in doc comment) may be used.');
            // @codeCoverageIgnoreEnd
        }
        if (count($php_type_node->genericTypes) !== 1) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception('NamedType has exactly one generic parameter.');
            // @codeCoverageIgnoreEnd
        }
        return $php_type_node->genericTypes[0];
    }

    /** @param \ReflectionClass<object> $class_info */
    public function extendsNamedType(\ReflectionClass $class_info): bool {
        return $class_info->getParentClass()
            && $class_info->getParentClass()->getName() === NamedType::class;
    }

    public function parseDocComment(string|false|null $doc_comment): PhpDocNode {
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
