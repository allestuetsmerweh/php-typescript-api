<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhpTypeScriptApi\Translator;

/**
 * @internal
 * @coversNothing
 */
class UnitTestCase extends TestCase {
    protected function setUp(): void {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', '1');
        date_default_timezone_set('UTC');

        Translator::resetInstance();
    }
}
