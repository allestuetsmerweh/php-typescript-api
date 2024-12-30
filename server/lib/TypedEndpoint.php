<?php

namespace PhpTypeScriptApi;

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
 */
abstract class TypedEndpoint implements EndpointInterface {
    use \Psr\Log\LoggerAwareTrait;

    /** @var \ReflectionClass<object> */
    private \ReflectionClass $endpointClass;
    private TypeNode $requestTypeNode;
    private TypeNode $responseTypeNode;
    private PhpStanUtils $phpStanUtils;

    public function __construct() {
        $this->phpStanUtils = new PhpStanUtils();
        $class_name = get_called_class();
        $this->endpointClass = new \ReflectionClass($class_name);
        $php_doc_node = $this->phpStanUtils->parseDocComment($this->endpointClass->getDocComment());
        $extends_type_node = $php_doc_node->getExtendsTagValues()[0]->type;
        if (!preg_match('/(^|\\\)TypedEndpoint$/', "{$extends_type_node->type}")) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception('Only classes directly extending TypedEndpoint (in doc comment) may be used.');
            // @codeCoverageIgnoreEnd
        }
        if (count($extends_type_node->genericTypes) !== 2) {
            // @codeCoverageIgnoreStart
            // Reason: phpstan does not allow testing this!
            throw new \Exception('TypedEndpoint has exactly two generic parameter.');
            // @codeCoverageIgnoreEnd
        }
        $this->requestTypeNode = $extends_type_node->genericTypes[0];
        $this->responseTypeNode = $extends_type_node->genericTypes[1];
    }

    /** Override to enjoy throttling! */
    public function shouldFailThrottling(): bool {
        return false;
    }

    /** Override to handle custom requests. */
    public function parseInput(Request $request): mixed {
        $input = json_decode($request->getContent(), true);
        // GET param `request`.
        if (!$input && $request->query->has('request')) {
            $input = json_decode($request->get('request'), true);
        }
        return $input;
    }

    /**
     * Note: The input is not required to be validated yet. We accept mixed.
     *
     * @return Response
     */
    public function call(mixed $input): mixed {
        if ($this->shouldFailThrottling()) {
            $this->logger?->error("Throttled user request");
            throw new HttpError(429, Translator::__('endpoint.too_many_requests'));
        }

        $result = ValidateVisitor::validate(
            $this->endpointClass,
            $input,
            $this->requestTypeNode
        );
        if (!$result->isValid()) {
            $this->logger?->warning("Bad user request", [$result->getErrors()]);
            throw new HttpError(400, Translator::__('endpoint.bad_input'), new ValidationError($result->getErrors()));
        }
        $this->logger?->info("Valid user request");

        try {
            $output = $this->handle($input);
        } catch (ValidationError $verr) {
            $this->logger?->warning("Bad user request", $verr->getStructuredAnswer());
            throw new HttpError(400, Translator::__('endpoint.bad_input'), $verr);
        } catch (HttpError $http_error) {
            $this->logger?->warning("HTTP error {$http_error->getCode()}", [$http_error]);
            throw $http_error;
        } catch (\Exception $exc) {
            $message = $exc->getMessage();
            $this->logger?->critical("Unexpected endpoint error: {$message}", $exc->getTrace());
            throw new HttpError(500, Translator::__('endpoint.internal_server_error'), $exc);
        }

        $result = ValidateVisitor::validate(
            $this->endpointClass,
            $output,
            $this->responseTypeNode
        );
        if (!$result->isValid()) {
            $this->logger?->critical("Bad output prohibited", [$result->getErrors()]);
            throw new HttpError(500, Translator::__('endpoint.internal_server_error'), new ValidationError($result->getErrors()));
        }
        $this->logger?->info("Valid user response");
        return $output;
    }

    /** @return array<string, string> */
    public function getNamedTsTypes(): array {
        $visitor = new TypeScriptVisitor($this->endpointClass);
        $traverser = new NodeTraverser([$visitor]);
        $traverser->traverse([$this->requestTypeNode]);
        $traverser->traverse([$this->responseTypeNode]);
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
        $visitor = new TypeScriptVisitor($this->endpointClass);
        $traverser = new NodeTraverser([$visitor]);
        [$ts_type_node] = $traverser->traverse([$this->requestTypeNode]);
        return "{$ts_type_node}";
    }

    public function getResponseTsType(): string {
        $visitor = new TypeScriptVisitor($this->endpointClass);
        $traverser = new NodeTraverser([$visitor]);
        [$ts_type_node] = $traverser->traverse([$this->responseTypeNode]);
        return "{$ts_type_node}";
    }

    /**
     * @param Request $input
     *
     * @return Response
     */
    abstract protected function handle(mixed $input): mixed;
}
