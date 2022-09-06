<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\Common;

use PhpTypeScriptApi\Translator;
use PHPUnit\Framework\TestCase;

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
