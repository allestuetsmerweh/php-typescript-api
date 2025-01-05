<?php

use PhpTypeScriptApi\TypedEndpoint;

/**
 * @extends TypedEndpoint<
 *   array{},
 *   array{},
 * >
 */
class EmptyTypedEndpoint extends TypedEndpoint {
    public static function getApiObjectClasses(): array {
        return [];
    }

    public function runtimeSetup(): void {
        // no runtime setup required.
    }

    public static function getIdent(): string {
        return 'EmptyTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        return [];
    }
}
