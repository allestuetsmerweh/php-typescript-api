<?php

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * @phpstan-type ImportAlias array{namespace: string, name: string}
 * @phpstan-type TypeAlias array{type: Node}
 * @phpstan-type Alias TypeAlias|ImportAlias
 * @phpstan-type NamespaceAliases array<string, Alias>
 * @phpstan-type AliasCache array<string, NamespaceAliases>
 */
class PhpStanUtils {
    public function getApiObjectTypeNode(string $name): ?Node {
        $class_info = $this->resolveApiObjectClass($name);
        if ($class_info === null) {
            return null;
        }
        $php_doc_node = $this->parseDocComment(
            $class_info->getDocComment(),
            $class_info->getFileName() ?: null,
        );
        $aliases = $this->getAliases($php_doc_node);
        $api_object_interface = ApiObjectInterface::class;
        $implements_node = null;
        foreach ($php_doc_node?->getImplementsTagValues() ?? [] as $node) {
            $generic_node = $node->type;
            if ("{$generic_node->type}" === $api_object_interface) {
                $implements_node = $this->resolveTypeAliases($generic_node->genericTypes[0], $aliases);
            }
        }
        return $implements_node;
    }

    /** @return \ReflectionClass<ApiObjectInterface<mixed>> */
    public function resolveApiObjectClass(string $name): ?\ReflectionClass {
        $class_info = $this->getReflectionClass($name);
        if (!$class_info?->implementsInterface(ApiObjectInterface::class)) {
            return null;
        }
        // @phpstan-ignore return.type
        return $class_info;
    }

    /**
     * @param NamespaceAliases $aliases
     */
    public function resolveTypeAliases(Node $node, array $aliases): Node {
        $visitor = new ResolveAliasesVisitor($this, $aliases);
        $traverser = new NodeTraverser([$visitor]);
        [$resolved_extends_node] = $traverser->traverse([$node]);
        return $resolved_extends_node;
    }

    /** @return NamespaceAliases */
    public function getAliases(?PhpDocNode $php_doc_node): array {
        $aliases = [];
        foreach ($php_doc_node?->getTypeAliasTagValues() ?? [] as $alias_node) {
            $aliases[$alias_node->alias] = ['type' => $alias_node->type];
        }
        foreach ($php_doc_node?->getTypeAliasImportTagValues() ?? [] as $import_node) {
            $alias = $import_node->importedAs ?? $import_node->importedAlias;
            $from = $import_node->importedFrom->name;
            $aliases[$alias] = ['namespace' => $from, 'name' => $import_node->importedAlias];
        }
        return $aliases;
    }

    /** @var AliasCache */
    protected array $alias_cache = [];
    protected int $recursion = 0;
    public int $max_recursion = 100;

    /**
     * @param Alias $alias
     */
    public function resolveAlias(array $alias): Node {
        if (isset($alias['type'])) {
            return clone $alias['type'];
        }
        $namespace = $alias['namespace'] ?? null;
        $name = $alias['name'] ?? null;
        assert($namespace !== null);
        assert($name !== null);
        if (!isset($this->alias_cache[$namespace])) {
            $class_info = $this->getReflectionClass($namespace);
            $import_php_doc_node = $this->parseDocComment(
                $class_info?->getDocComment(),
                $class_info?->getFileName() ?: null,
            );
            $this->alias_cache[$namespace] = $this->getAliases($import_php_doc_node);
        }
        $import_alias = $this->alias_cache[$namespace][$name] ?? null;
        if ($import_alias === null) {
            throw new \Exception("Failed importing {$name} from {$namespace}");
        }
        $this->recursion++;
        if ($this->recursion > $this->max_recursion) {
            throw new \Exception("Maximum recusion level ({$this->max_recursion}) reached: Failed importing {$name} from {$namespace}");
        }
        $resolved_alias = $this->resolveAlias($import_alias);
        $this->recursion--;
        return $resolved_alias;
    }

    public function parseDocComment(
        string|false|null $doc_comment,
        ?string $file_path = null,
    ): ?PhpDocNode {
        if (!$doc_comment) {
            return null;
        }
        [$namespace, $scope] = $this->getFileScopeInfo($file_path);
        $config = new ParserConfig(usedAttributes: []);
        $lexer = new Lexer($config);
        $constExprParser = new ConstExprParser($config);
        $typeParser = new TypeParser($config, $constExprParser);
        $phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);
        $tokens = array_map(function ($token) use ($namespace, $scope) {
            if ($token[1] === Lexer::TOKEN_IDENTIFIER) {
                if (isset($scope[$token[0]])) {
                    // Imported scope
                    return [$scope[$token[0]], $token[1], $token[2]];
                }
                if ($namespace) {
                    $fqn = "{$namespace}\\{$token[0]}";
                    $class_info = $this->getReflectionClass($fqn);
                    if ($class_info !== null) {
                        // Namespace scope
                        return [$fqn, $token[1], $token[2]];
                    }
                }
            }
            return $token;
        }, $lexer->tokenize($doc_comment));
        return $phpDocParser->parse(new TokenIterator($tokens));
    }

    /** @return array{0: ?string, 1: array<string, string>} */
    public function getFileScopeInfo(?string $file_path): array {
        $namespace = null;
        $scope = [];
        if ($file_path === null) {
            return [$namespace, $scope];
        }
        $content = @\file_get_contents($file_path);
        if ($content === false) {
            return [$namespace, $scope];
        }
        $tokens = \token_get_all($content);
        while (\key($tokens) !== null) {
            $token = \current($tokens);
            if (\is_array($token) && $token[0] === T_NAMESPACE) {
                \next($tokens); // whitespace
                $token = \next($tokens); // fqn
                if (\is_array($token) && $token[0] === T_NAME_QUALIFIED) {
                    $namespace = $token[1];
                }
            }
            if (\is_array($token) && $token[0] === T_USE) {
                \next($tokens); // whitespace
                $token = \next($tokens); // fqn
                if (\is_array($token) && $token[0] === T_NAME_QUALIFIED) {
                    $fqn = $token[1];
                    $idx = \key($tokens);
                    if ($tokens[$idx + 1][0] === ';' || $tokens[$idx + 3][0] === ';') {
                        $fqnParts = explode('\\', $fqn);
                        $scope[$fqnParts[\array_key_last($fqnParts)]] = $fqn;
                    } elseif ($tokens[$idx + 2] && $tokens[$idx + 2][0] === T_AS) {
                        \next($tokens); // whitespace
                        \next($tokens); // as
                        \next($tokens); // whitespace
                        $token = \next($tokens); // alias
                        if ($token) {
                            $scope[$token[1]] = $fqn;
                        }
                    }
                }
            }
            $token = \next($tokens);
            if (\is_array($token) && $token[0] === T_CLASS) {
                // Stop at the class declaration.
                // No more use statements expected here
                break;
            }
        }
        return [$namespace, $scope];
    }

    /** @param AliasCache $alias_cache */
    public function getPrettyAliasCache(array $alias_cache): string {
        $out = '---';
        foreach ($alias_cache as $namespace => $aliases) {
            $out .= "\n{$namespace}\n";
            foreach ($aliases as $name => $alias) {
                $out .= "    {$name} => {$this->getPrettyAlias($alias)}\n";
            }
        }
        $out .= "---\n";
        return $out;
    }

    /** @param Alias $alias */
    public function getPrettyAlias(array $alias): string {
        if (isset($alias['type'])) {
            return "{$alias['type']}";
        }
        if (isset($alias['namespace'])) {
            return "{$alias['namespace']}::{$alias['name']}";
        }
        $enc_alias = json_encode($alias) ?: '';
        return "INVALID ALIAS: {$enc_alias}";
    }

    /** @return ?\ReflectionClass<object> */
    protected function getReflectionClass(?string $name): ?\ReflectionClass {
        try {
            // @phpstan-ignore argument.type
            return new \ReflectionClass($name);
        } catch (\ReflectionException $th) {
            return null;
        }
    }
}
