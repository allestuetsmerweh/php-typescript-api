<?php

declare(strict_types=1);

class FakeLogHandler implements Monolog\Handler\HandlerInterface {
    public $records = [];

    public function isHandling(array $args): bool {
        return true;
    }

    public function handle(array $record): bool {
        $this->records[] = $record;
        return true;
    }

    public function handleBatch(array $records): void {
    }

    public function close(): void {
    }

    public function getPrettyRecords() {
        return array_map(function ($record) {
            $level_name = $record['level_name'];
            $message = $record['message'];
            return "{$level_name} {$message}";
        }, $this->records);
    }
}
