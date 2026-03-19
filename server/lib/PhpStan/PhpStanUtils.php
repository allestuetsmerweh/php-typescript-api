<?php

namespace PhpTypeScriptApi\PhpStan;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
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
    public function getApiObjectTypeNode(string $name): ?TypeNode {
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
        if (!$implements_node instanceof TypeNode) {
            throw new \Exception("getApiObjectTypeNode: Implements node {$implements_node} is not a TypeNode");
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
        [$resolved_node] = $traverser->traverse([$node]);
        return $resolved_node;
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
    public function resolveAlias(array $alias): TypeNode {
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

    /** @param class-string $class_name */
    public function parseClassDocComment(string $class_name): ?PhpDocNode {
        return $this->parseReflectionClassDocComment(new \ReflectionClass($class_name));
    }

    /** @param \ReflectionClass<object> $reflection_class */
    public function parseReflectionClassDocComment(\ReflectionClass $reflection_class): ?PhpDocNode {
        return $this->parseDocComment(
            $reflection_class->getDocComment(),
            $reflection_class->getFileName() ?: null,
        );
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

    /**
     * @param class-string $class_name
     * @param class-string $superclass_name
     *
     * @return array<TypeNode>
     */
    public function getSuperGenerics(string $class_name, string $superclass_name): array {
        if (!is_subclass_of($class_name, $superclass_name)) {
            throw new \Exception("getSuperGenerics: {$class_name} is not a subclass of {$superclass_name}");
        }
        $esc_superclass_name = preg_quote($superclass_name);
        $superclass_info = new \ReflectionClass($superclass_name);
        $is_interface = $superclass_info->isInterface();

        $class_info = new \ReflectionClass($class_name);
        $extends_node = null;
        do {
            $php_doc_node = $this->parseReflectionClassDocComment($class_info);
            $namespace_aliases = [
                ...$this->getTemplateAliases($php_doc_node, $extends_node?->type),
                ...$this->getAliases($php_doc_node),
            ];
            if ($is_interface) {
                foreach (($php_doc_node?->getImplementsTagValues() ?? []) as $implements_node) {
                    if (!preg_match("/(^|\\\\){$esc_superclass_name}$/", "{$implements_node->type->type}")) {
                        continue;
                    }
                    $resolved_implements_node = $this->resolveTypeAliases($implements_node, $namespace_aliases);
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
                $resolved_extends_node = $this->resolveTypeAliases($extends_node, $namespace_aliases);
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
     * @return NamespaceAliases
     */
    public function getTemplateAliases(
        ?PhpDocNode $php_doc_node,
        ?GenericTypeNode $generic_node,
    ): array {
        if (!$php_doc_node) {
            return [];
        }
        $aliases = [];
        $args = $generic_node->genericTypes ?? [];
        $template_nodes = $php_doc_node->getTemplateTagValues();
        $min_args = 0;
        $max_args = count($template_nodes);
        foreach ($template_nodes as $template_node) {
            $min_args += $template_node->default === null ? 1 : 0;
        }
        $pretty_range = $min_args === $max_args ? $min_args : "{$min_args}-{$max_args}";
        if (count($args) < $min_args || count($args) > $max_args) {
            $pretty_generics = implode(', ', $args);
            throw new \Exception("Expected {$pretty_range} generic arguments, but got '{$generic_node?->type}<{$pretty_generics}>'");
        }
        for ($i = 0; $i < count($template_nodes); $i++) {
            $node = $template_nodes[$i];
            $value = $args[$i] ?? $node->default;
            if ($value === null) {
                // @codeCoverageIgnoreStart
                // Reason: phpstan does not allow testing this!
                $pretty_generics = implode(', ', $args);
                throw new \Exception("This should never happen: Template[{$i}] is null. Expected {$pretty_range} generic arguments, got '{$generic_node?->type}<{$pretty_generics}>'");
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
