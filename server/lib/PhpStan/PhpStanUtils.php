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
    public function getAliasTypeNode(string $name, \ReflectionClass $class_info): TypeNode {
        $php_doc_node = $this->parseDocComment($class_info->getDocComment());
        // TODO: Use array_find (PHP 8.4)
        $alias_node = null;
        foreach ($php_doc_node->getTypeAliasTagValues() as $node) {
            if ($node->alias === $name) {
                $alias_node = $node;
            }
        }
        if ($alias_node === null) {
            throw new \Exception("Type alias not found: {$name}");
        }
        return $alias_node->type;
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
