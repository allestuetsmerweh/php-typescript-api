<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

class DateTimeField extends AbstractTemporalField {
    protected function getRegex() {
        return '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';
    }
}
