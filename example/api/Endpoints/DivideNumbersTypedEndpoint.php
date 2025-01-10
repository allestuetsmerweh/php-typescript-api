<?php

require_once __DIR__.'/DivideTypedEndpoint.php';

/**
 * @phpstan-import-type DefaultNumberType from DivideTypedEndpoint
 *
 * @extends DivideTypedEndpoint<DefaultNumberType>
 */
class DivideNumbersTypedEndpoint extends DivideTypedEndpoint {
    public function configure(): void {
        $this->phpStanUtils->registerTypeImport(DivideTypedEndpoint::class);
    }
}
