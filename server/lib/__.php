<?php

// @codeCoverageIgnoreStart
// Reason: Functions can't be tested...
function __(?string $id, array $parameters = [], string $domain = null, string $locale = null): string {
    $translator = PhpTypescriptApi\Translator::getInstance();
    return $translator->trans($id, $parameters, $domain, $locale);
}
// @codeCoverageIgnoreEnd
