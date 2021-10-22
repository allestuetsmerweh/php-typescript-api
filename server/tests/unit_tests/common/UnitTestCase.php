<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../vendor/autoload.php';

/**
 * @internal
 * @coversNothing
 */
class UnitTestCase extends TestCase {
    protected function setUp(): void {
        date_default_timezone_set('UTC');
    }
}
