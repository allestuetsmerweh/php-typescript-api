<?php

use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\IsoDateTime;
use PhpTypeScriptApi\PhpStan\IsoTime;
use PhpTypeScriptApi\TypedEndpoint;

/**
 * @extends TypedEndpoint<
 *   array{date: IsoDate, time: \PhpTypeScriptApi\PhpStan\IsoTime},
 *   array{dateTime: IsoDateTime},
 * >
 */
class CombineDateTimeTypedEndpoint extends TypedEndpoint {
    public static function getApiObjectClasses(): array {
        return [IsoDate::class, IsoTime::class, IsoDateTime::class];
    }

    public function runtimeSetup(): void {
        // no runtime setup required
    }

    public static function getIdent(): string {
        return 'CombineDateTimeTypedEndpoint';
    }

    protected function handle(mixed $input): mixed {
        $date = $input['date']->format('Y-m-d');
        $time = $input['time']->format('H:i:s');
        return [
            'dateTime' => new IsoDateTime("{$date} {$time}"),
        ];
    }
}
