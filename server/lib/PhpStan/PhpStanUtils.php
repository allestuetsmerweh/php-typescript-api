<?php

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * @phpstan-type ImportAlias array{namespace: string, name: string}
 * @phpstan-type TypeAlias array{type: TypeNode}
 * @phpstan-type Alias TypeAlias|ImportAlias
 * @phpstan-type NamespaceAliases array<string, Alias>
 * @phpstan-type AliasCache array<string, NamespaceAliases>
 */
class PhpStanUtils {
    public int $max_recursion = 100;

    public function getApiObjectTypeNode(string $class_name): ?TypeNode {
        try {
            $generics = $this->getSuperGenerics($class_name, ApiObjectInterface::class);
        } catch (\Throwable $th) {
            return null;
        }
        $implements_node = $generics[0] ?? null;
        if (!$implements_node instanceof TypeNode) {
            return null;
        }
        return $implements_node;
    }

    /**
     * @param ?array<TypeNode> $generic_args
     */
    public function resolveType(Node $node, string $class_name, ?array $generic_args = null): Node {
        $aliases = $this->getAliases($class_name, $generic_args);
        $visitor = new ReplaceNodesVisitor(function (Node $node) use (
            $aliases,
            $class_name,
            $generic_args,
        ) {
            if (!$node instanceof IdentifierTypeNode) {
                return $node;
            }
            $alias = $aliases[$node->name] ?? null;
            if ($alias === null) {
                return $node;
            }
            // Resolve ImportAlias
            $namespace = null;
            for ($i = 0; isset($alias['namespace']); $i++) {
                if ($i > $this->max_recursion) {
                    throw new \Exception("Maximum recusion level ({$this->max_recursion}) reached: Failed importing {$alias['name']} from {$alias['namespace']}");
                }
                $namespace = $alias['namespace'];
                $alias = $this->resolveImportAlias($alias);
            }
            if (!isset($alias['type'])) {
                return $node;
            }
            if ($namespace === null) {
                return $this->resolveType($alias['type'], $class_name, $generic_args);
            }
            return $this->resolveType($alias['type'], $namespace, []);
        });
        $traverser = new NodeTraverser([$visitor]);
        [$resolved_node] = $traverser->traverse([$node]);
        return $resolved_node;
    }

    /**
     * @param ?array<TypeNode> $generic_args
     *
     * @return array{0: Node, 1: array<string, Node>}
     */
    public function rewriteType(Node $node, string $class_name, ?array $generic_args = null): array {
        $aliases = $this->getAliases($class_name, $generic_args);
        $exports = [];
        $visitor = new ReplaceNodesVisitor(function (Node $node) use (
            $aliases,
            $class_name,
            $generic_args,
            &$exports,
        ) {
            if (!$node instanceof IdentifierTypeNode) {
                return $node;
            }
            $alias = $aliases[$node->name] ?? null;
            if ($alias === null) {
                return $node;
            }
            $esc_class_name = str_replace('\\', '_', $class_name);
            $md5_generic_args = $generic_args ? md5(implode(',', $generic_args)) : '';
            $absolute_name = "{$esc_class_name}{$md5_generic_args}_{$node->name}";
            if (isset($alias['type'])) {
                [$new_node, $new_exports] = $this->rewriteType($alias['type'], $class_name, $generic_args);
                $exports = [
                    ...$exports,
                    ...$new_exports,
                    $absolute_name => $new_node,
                ];
                return new IdentifierTypeNode($absolute_name);
            }
            if (isset($alias['namespace'])) {
                $import_node = new IdentifierTypeNode($alias['name']);
                [$new_node, $new_exports] = $this->rewriteType($import_node, $alias['namespace']);
                $exports = [
                    ...$exports,
                    ...$new_exports,
                    $absolute_name => $new_node,
                ];
                return new IdentifierTypeNode($absolute_name);
            }
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            $enc_alias = json_encode($alias) ?: '';
            throw new \Exception("Invalid alias: {$enc_alias}");
            // @codeCoverageIgnoreEnd
        });
        $traverser = new NodeTraverser([$visitor]);
        [$resolved_node] = $traverser->traverse([$node]);
        return [$resolved_node, $exports];
    }

    /** @var AliasCache */
    protected array $alias_cache = [];

    /**
     * @param ?array<TypeNode> $generic_args
     *
     * @return NamespaceAliases */
    public function getAliases(string $class_name, ?array $generic_args = null): array {
        $php_doc_node = null;
        $aliases = $this->alias_cache[$class_name] ?? null;
        if ($aliases === null) {
            $php_doc_node = $this->parseClassDocComment($class_name);
            $aliases = $this->getAliasesInPhpDocNode($php_doc_node);
            $this->alias_cache[$class_name] = $aliases;
        }
        if ($generic_args === null) {
            return $aliases;
        }
        $php_doc_node ??= $this->parseClassDocComment($class_name);
        return [...$aliases, ...$this->getTemplateAliases($php_doc_node, $generic_args)];
    }

    /** @return NamespaceAliases */
    protected function getAliasesInPhpDocNode(?PhpDocNode $php_doc_node): array {
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

    /**
     * @param ImportAlias $import_alias
     *
     * @return Alias
     */
    public function resolveImportAlias(array $import_alias): array {
        $namespace = $import_alias['namespace'];
        $name = $import_alias['name'];
        $aliases = $this->getAliases($namespace);
        $alias = $aliases[$name] ?? null;
        if ($alias === null) {
            throw new \Exception("Failed importing {$name} from {$namespace}");
        }
        return $alias;
    }

    public function parseClassDocComment(string $class_name): ?PhpDocNode {
        $reflection_class = $this->getReflectionClass($class_name);
        if (!$reflection_class) {
            return null;
        }
        return $this->parseReflectionClassDocComment($reflection_class);
    }

    /** @param \ReflectionClass<object> $reflection_class */
    public function parseReflectionClassDocComment(\ReflectionClass $reflection_class): ?PhpDocNode {
        return $this->parseDocComment(
            $reflection_class->getDocComment(),
            $reflection_class->getFileName() ?: null,
        );
    }

    /** @var array<string, PhpDocNode> */
    protected array $doc_comment_cache = [];

    public function parseDocComment(
        string|false|null $doc_comment,
        ?string $file_path = null,
    ): ?PhpDocNode {
        if (!$doc_comment) {
            return null;
        }
        $md5 = md5($doc_comment);
        $cached = $this->doc_comment_cache[$md5] ?? null;
        if ($cached) {
            return $cached;
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
        $php_doc_node = $phpDocParser->parse(new TokenIterator($tokens));
        $this->doc_comment_cache[$md5] = $php_doc_node;
        return $php_doc_node;
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

    /**
     * @param ?callable(Node, string, array<Node>): Node $process_node_fn
     *
     * @return array<TypeNode>
     */
    public function getSuperGenerics(string $class_name, string $superclass_name, ?callable $process_node_fn = null): array {
        if (!is_subclass_of($class_name, $superclass_name)) {
            throw new \Exception("getSuperGenerics: {$class_name} is not a subclass of {$superclass_name}");
        }
        $esc_superclass_name = preg_quote($superclass_name);
        $superclass_info = $this->getReflectionClass($superclass_name);
        if ($superclass_info === null) {
            throw new \Exception("getSuperGenerics: Invalid superclass {$superclass_name}");
        }
        $is_interface = $superclass_info->isInterface();
        if ($process_node_fn === null) {
            $process_node_fn = function (Node $node, string $class_name, array $generic_args) {
                return $this->resolveType($node, $class_name, $generic_args);
            };
        }

        $class_info = $this->getReflectionClass($class_name);
        if ($class_info === null) {
            throw new \Exception("getSuperGenerics: Invalid class {$class_name}");
        }
        $extends_node = null;
        do {
            $php_doc_node = $this->parseReflectionClassDocComment($class_info);
            $class_name = $class_info->getName();
            $generic_args = $extends_node?->type->genericTypes ?? [];
            if ($is_interface) {
                // Note: We don't support interfaces extending other interfaces.
                foreach (($php_doc_node?->getImplementsTagValues() ?? []) as $implements_node) {
                    if (!preg_match("/(^|\\\\){$esc_superclass_name}$/", "{$implements_node->type->type}")) {
                        continue;
                    }
                    $resolved_implements_node = $process_node_fn($implements_node, $class_name, $generic_args);
                    if (!$resolved_implements_node instanceof ImplementsTagValueNode) {
                        // @codeCoverageIgnoreStart
                        // Reason: phpstan does not allow testing this!
                        throw new \Exception("getSuperGenerics: Implements node {$implements_node} resolved to non-implements node {$resolved_implements_node}");
                        // @codeCoverageIgnoreEnd
                    }
                    return $resolved_implements_node->type->genericTypes;
                }
            }
            $extends_node = $php_doc_node?->getExtendsTagValues()[0] ?? null;
            if ($extends_node) {
                $resolved_extends_node = $process_node_fn($extends_node, $class_name, $generic_args);
                if (!$resolved_extends_node instanceof ExtendsTagValueNode) {
                    // @codeCoverageIgnoreStart
                    // Reason: phpstan does not allow testing this!
                    throw new \Exception("getSuperGenerics: Extends node {$extends_node} resolved to non-extends node {$resolved_extends_node}");
                    // @codeCoverageIgnoreEnd
                }
                $extends_node = $resolved_extends_node;
            }
            $parent_class_info = $class_info->getParentClass() ?: null;
            if (!$parent_class_info || $parent_class_info->getName() === $superclass_name) {
                break;
            }
            $class_info = $parent_class_info;
        } while ($parent_class_info);
        if (!$extends_node) {
            return [];
        }
        if (!preg_match("/(^|\\\\){$esc_superclass_name}$/", "{$extends_node->type->type}")) {
            throw new \Exception("{$class_name} does not extend {$superclass_name}");
        }
        return $extends_node->type->genericTypes;
    }

    /**
     * @param array<TypeNode> $generic_args
     *
     * @return NamespaceAliases
     */
    public function getTemplateAliases(
        ?PhpDocNode $php_doc_node,
        array $generic_args,
    ): array {
        if (!$php_doc_node) {
            return [];
        }
        $aliases = [];
        $template_nodes = $php_doc_node->getTemplateTagValues();
        $min_args = 0;
        $max_args = count($template_nodes);
        $num_args = count($generic_args);
        foreach ($template_nodes as $template_node) {
            $min_args += $template_node->default === null ? 1 : 0;
        }
        $pretty_range = $min_args === $max_args ? $min_args : "{$min_args}-{$max_args}";
        if ($num_args < $min_args || $num_args > $max_args) {
            $pretty_generics = implode(', ', $generic_args);
            throw new \Exception("Expected {$pretty_range} generic arguments, but got '<{$pretty_generics}>'");
        }
        for ($i = 0; $i < count($template_nodes); $i++) {
            $node = $template_nodes[$i];
            $value = $generic_args[$i] ?? $node->default;
            if ($value === null) {
                // @codeCoverageIgnoreStart
                // Reason: phpstan does not allow testing this!
                $pretty_generics = implode(', ', $generic_args);
                throw new \Exception("This should never happen: Template[{$i}] is null. Expected {$pretty_range} generic arguments, got '<{$pretty_generics}>'");
                // @codeCoverageIgnoreEnd
            }
            $aliases[$node->name] = ['type' => $value];
        }
        return $aliases;
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
        // @codeCoverageIgnoreStart
        // Reason: phpstan does not allow testing this!
        $enc_alias = json_encode($alias) ?: '';
        return "INVALID ALIAS: {$enc_alias}";
        // @codeCoverageIgnoreEnd
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
