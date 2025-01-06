<?php

use PhpTypeScriptApi\PhpStan\PhpStanUtils;

require_once __DIR__.'/DivideTypedEndpoint.php';

/**
 * @phpstan-import-type DefaultNumberType from DivideTypedEndpoint
 *
 * @extends DivideTypedEndpoint<DefaultNumberType>
 */
class DivideNumbersTypedEndpoint extends DivideTypedEndpoint {
    public function configure(): void {
        PhpStanUtils::registerTypeImport(DivideTypedEndpoint::class);
    }
}
