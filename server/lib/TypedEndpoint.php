<?php

namespace PhpTypeScriptApi;

use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\PhpStan\ResolveAliasesVisitor;
use PhpTypeScriptApi\PhpStan\TypeScriptVisitor;
use PhpTypeScriptApi\PhpStan\ValidateVisitor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @template Request
 * @template Response
 */
abstract class TypedEndpoint implements EndpointInterface {
    use \Psr\Log\LoggerAwareTrait;

    protected PhpStanUtils $phpStanUtils;
    private ?TypeNode $requestTypeNode = null;
    private ?TypeNode $responseTypeNode = null;
    /** @var ?array<string, TypeNode> */
    private ?array $aliasNodes = null;

    public function __construct() {
        $this->phpStanUtils = new PhpStanUtils();
    }

    public function parseType(): void {
        $this->configure();
        $class_name = get_called_class();
        $class_info = new \ReflectionClass($class_name);
        $this->aliasNodes = [];
        $template_aliases = [];
        $extends_node = null;
        while ($class_info->getParentClass()) {
            $php_doc_node = $this->phpStanUtils->parseDocComment($class_info->getDocComment());
            $template_aliases = $this->getTemplateAliases($php_doc_node, $extends_node);
            $this->aliasNodes = [
                ...$this->aliasNodes,
                ...$this->phpStanUtils->getAliases($php_doc_node),
            ];
            $extends_node = $this->getResolvedExtendsNode($php_doc_node, $template_aliases);
            $parent_class_info = $class_info->getParentClass();
            if ($parent_class_info->getName() === TypedEndpoint::class) {
                break;
            }
            $class_info = $parent_class_info;
        }
        $this->aliasNodes = [
            ...$this->aliasNodes,
            ...$template_aliases,
        ];
        if (!$extends_node) {
            throw new \Exception("Could not parse type for {$class_name}");
        }
        if (!preg_match('/(^|\\\)TypedEndpoint$/', "{$extends_node->type->type}")) {
            throw new \Exception("{$class_name} does not extend TypedEndpoint");
        }
        if (count($extends_node->type->genericTypes) !== 2) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            $pretty_generics = implode(', ', $extends_node->type->genericTypes);
            throw new \Exception("{$class_name} must provide two generics to TypedEndpoint, provided TypedEndpoint<{$pretty_generics}>");
            // @codeCoverageIgnoreEnd
        }
        $this->requestTypeNode = $extends_node->type->genericTypes[0];
        $this->responseTypeNode = $extends_node->type->genericTypes[1];
    }

    public function configure(): void {
        // Do nothing by default
    }

    /**
     * @return array<string, TypeNode>
     */
    protected function getTemplateAliases(
        ?PhpDocNode $php_doc_node,
        ?ExtendsTagValueNode $previous_extends_node,
    ): array {
        if (!$php_doc_node) {
            return [];
        }
        $aliases = [];
        $args = $previous_extends_node?->type->genericTypes ?? [];
        $template_nodes = $php_doc_node->getTemplateTagValues();
        $min_args = 0;
        $max_args = count($template_nodes);
        foreach ($template_nodes as $template_node) {
            $min_args += $template_node->default === null ? 1 : 0;
        }
        $pretty_range = $min_args === $max_args ? $min_args : "{$min_args}-{$max_args}";
        if (count($args) < $min_args || count($args) > $max_args) {
            $pretty_generics = implode(', ', $args);
            throw new \Exception("Expected {$pretty_range} generic arguments, but got '{$previous_extends_node?->type->type}<{$pretty_generics}>'");
        }
        for ($i = 0; $i < count($template_nodes); $i++) {
            $node = $template_nodes[$i];
            $value = $args[$i] ?? $node->default;
            if ($value === null) {
                // @codeCoverageIgnoreStart
                // Reason: phpstan does not allow testing this!
                $pretty_generics = implode(', ', $args);
                throw new \Exception("This should never happen: Template[{$i}] is null. Expected {$pretty_range} generic arguments, got '{$previous_extends_node?->type->type}<{$pretty_generics}>'");
                // @codeCoverageIgnoreEnd
            }
            $aliases[$node->name] = $value;
        }
        return $aliases;
    }

    /**
     * @param array<string, TypeNode> $template_aliases
     */
    protected function getResolvedExtendsNode(
        ?PhpDocNode $php_doc_node,
        array $template_aliases,
    ): ?ExtendsTagValueNode {
        $extends_type_node = $php_doc_node?->getExtendsTagValues()[0] ?? null;
        if (!$extends_type_node) {
            return null;
        }

        $visitor = new ResolveAliasesVisitor($template_aliases);
        $traverser = new NodeTraverser([$visitor]);
        [$resolved_extends_node] = $traverser->traverse([$extends_type_node]);
        if (!($resolved_extends_node instanceof ExtendsTagValueNode)) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception("Expected ExtendsTagValueNode, but got {$resolved_extends_node}");
            // @codeCoverageIgnoreEnd
        }
        return $resolved_extends_node;
    }

    public function setup(): void {
        $this->parseType();
        $this->runtimeSetup();
    }

    public function runtimeSetup(): void {
        // Do nothing by default
    }

    /** Override to enjoy throttling! */
    public function shouldFailThrottling(): bool {
        return false;
    }

    /** Override to handle custom requests. */
    public function parseInput(Request $request): mixed {
        $input = json_decode($request->getContent(), true);
        if (!json_last_error()) {
            return $input;
        }
        // GET param `request`.
        $request_param = $request->get('request');
        if (!is_string($request_param)) {
            return null;
        }
        return json_decode($request_param, true);
    }

    /**
     * Note: The input is not required to be validated yet. We accept mixed.
     * Note: The output is serialized, i.e. not necessarily of type `Response`.
     */
    public function call(mixed $raw_input): mixed {
        if ($this->shouldFailThrottling()) {
            $this->logger?->notice("Throttled user request");
            throw new HttpError(429, Translator::__('endpoint.too_many_requests'));
        }

        $result = ValidateVisitor::validateDeserialize(
            $this->phpStanUtils,
            $raw_input,
            $this->getRequestTypeNode(),
            $this->getAliasNodes(),
        );
        if (!$result->isValid()) {
            $this->logger?->notice("Bad user request", [$result->getErrors()]);
            throw new HttpError(400, Translator::__('endpoint.bad_input'), new ValidationError($result->getErrors()));
        }
        $this->logger?->info("Valid user request");
        $input = $result->getValue();

        try {
            $raw_output = $this->handle($input);
        } catch (ValidationError $verr) {
            $this->logger?->notice("Bad user request", $verr->getStructuredAnswer());
            throw new HttpError(400, Translator::__('endpoint.bad_input'), $verr);
        } catch (HttpError $http_error) {
            $this->logger?->notice("HTTP error {$http_error->getCode()}", [$http_error]);
            throw $http_error;
        } catch (\Exception $exc) {
            $message = $exc->getMessage();
            $this->logger?->critical("Unexpected endpoint error: {$message}", $exc->getTrace());
            throw new HttpError(500, Translator::__('endpoint.internal_server_error'), $exc);
        }

        $result = ValidateVisitor::validateSerialize(
            $this->phpStanUtils,
            $raw_output,
            $this->getResponseTypeNode(),
            $this->getAliasNodes(),
        );
        if (!$result->isValid()) {
            $this->logger?->critical("Bad output prohibited", [$result->getErrors()]);
            throw new HttpError(500, Translator::__('endpoint.internal_server_error'), new ValidationError($result->getErrors()));
        }
        $this->logger?->info("Valid user response");
        return $result->getValue();
    }

    /** @return array<string, string> */
    public function getNamedTsTypes(): array {
        $visitor = new TypeScriptVisitor($this->phpStanUtils, $this->getAliasNodes());
        $traverser = new NodeTraverser([$visitor]);
        $traverser->traverse([$this->getRequestTypeNode()]);
        $traverser->traverse([$this->getResponseTypeNode()]);
        $named_ts_types = [];
        // We're recursively adding exported classes.
        // An array in PHP is actually an ordered map, so this should work.
        for ($i = 0; $i < count($visitor->exported_classes); $i++) {
            $name = array_keys($visitor->exported_classes)[$i];
            $php_type_node = $visitor->exported_classes[$name];
            [$ts_type_node] = $traverser->traverse([$php_type_node]);
            $named_ts_types[$name] = "{$ts_type_node}";
        }
        return $named_ts_types;
    }

    public function getRequestTsType(): string {
        $visitor = new TypeScriptVisitor($this->phpStanUtils, $this->getAliasNodes());
        $traverser = new NodeTraverser([$visitor]);
        [$ts_type_node] = $traverser->traverse([$this->getRequestTypeNode()]);
        return "{$ts_type_node}";
    }

    public function getResponseTsType(): string {
        $visitor = new TypeScriptVisitor($this->phpStanUtils, $this->getAliasNodes());
        $traverser = new NodeTraverser([$visitor]);
        [$ts_type_node] = $traverser->traverse([$this->getResponseTypeNode()]);
        return "{$ts_type_node}";
    }

    /** @return array<string, TypeNode> */
    protected function getAliasNodes(): array {
        if ($this->aliasNodes === null) {
            $this->parseType();
        }
        if ($this->aliasNodes === null) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception('Must be set now');
            // @codeCoverageIgnoreEnd
        }
        return $this->aliasNodes;
    }

    protected function getRequestTypeNode(): TypeNode {
        if ($this->requestTypeNode === null) {
            $this->parseType();
        }
        if ($this->requestTypeNode === null) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception('Must be set now');
            // @codeCoverageIgnoreEnd
        }
        return $this->requestTypeNode;
    }

    protected function getResponseTypeNode(): TypeNode {
        if ($this->responseTypeNode === null) {
            $this->parseType();
        }
        if ($this->responseTypeNode === null) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception('Must be set now');
            // @codeCoverageIgnoreEnd
        }
        return $this->responseTypeNode;
    }

    /**
     * @param Request $input
     *
     * @return Response
     */
    abstract protected function handle(mixed $input): mixed;
}
