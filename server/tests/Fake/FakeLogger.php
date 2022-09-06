<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\Fake;

use Monolog\Logger;

class FakeLogger extends Logger {
    public $handler;

    public static function create($name = '') {
        $logger = new FakeLogger($name);
        $logger->handler = new FakeLogHandler();
        $logger->pushHandler($logger->handler);
        return $logger;
    }
}
