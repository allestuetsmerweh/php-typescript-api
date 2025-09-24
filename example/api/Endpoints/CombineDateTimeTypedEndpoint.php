<?php

use PhpTypeScriptApi\PhpStan\IsoDate;
use PhpTypeScriptApi\PhpStan\IsoDateTime;
use PhpTypeScriptApi\TypedEndpoint;

/**
 * @extends TypedEndpoint<
 *   array{date: IsoDate, time: \PhpTypeScriptApi\PhpStan\IsoTime},
 *   array{dateTime: IsoDateTime},
 * >
 */
class CombineDateTimeTypedEndpoint extends TypedEndpoint {
    protected function handle(mixed $input): mixed {
        $date = $input['date']->format('Y-m-d');
        $time = $input['time']->format('H:i:s');
        return [
            'dateTime' => new IsoDateTime("{$date} {$time}"),
        ];
    }
}
