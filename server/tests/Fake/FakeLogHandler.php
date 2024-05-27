<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\Fake;

use Monolog\Handler\HandlerInterface;
use Monolog\LogRecord;

class FakeLogHandler implements HandlerInterface {
    /** @var array<LogRecord> */
    public array $records = [];

    public function isHandling(LogRecord $record): bool {
        return true;
    }

    public function handle(LogRecord $record): bool {
        $this->records[] = $record;
        return true;
    }

    public function handleBatch(array $records): void {
    }

    public function close(): void {
    }

    /** @return array<string> */
    public function getPrettyRecords(): array {
        return array_map(function ($record) {
            $arr = $record->toArray();
            $level_name = $arr['level_name'];
            $message = $arr['message'];
            return "{$level_name} {$message}";
        }, $this->records);
    }
}
