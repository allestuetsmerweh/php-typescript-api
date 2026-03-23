<?php

namespace PhpTypeScriptApi;

use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PhpTypeScriptApi\Fields\ValidationError;
use PhpTypeScriptApi\PhpStan\PhpStanUtils;
use PhpTypeScriptApi\PhpStan\TypeScriptVisitor;
use PhpTypeScriptApi\PhpStan\ValidateVisitor;
use Symfony\Component\HttpFoundation\Request;

/**
 * @template Request
 * @template Response
 *
 * @phpstan-import-type NamespaceAliases from PhpStanUtils
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
        $class_name = get_called_class();
        $this->aliasNodes = [];
        $fn = function (Node $node, string $class_name, array $generic_args) {
            [$node, $new_exports] = $this->phpStanUtils->rewriteType($node, $class_name, $generic_args);
            foreach ($new_exports as $key => $value) {
                if (!$value instanceof TypeNode) {
                    throw new \Exception("Exporting non-TypeNode: {$value}");
                }
                $this->aliasNodes[$key] = $value;
            }
            return $node;
        };
        $generics = $this->phpStanUtils->getSuperGenerics($class_name, TypedEndpoint::class, $fn);
        if (count($generics) !== 2) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            $pretty_generics = implode(', ', $generics);
            throw new \Exception("{$class_name} must provide two generics to TypedEndpoint, provided TypedEndpoint<{$pretty_generics}>");
            // @codeCoverageIgnoreEnd
        }
        $this->requestTypeNode = $generics[0];
        $this->responseTypeNode = $generics[1];
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
