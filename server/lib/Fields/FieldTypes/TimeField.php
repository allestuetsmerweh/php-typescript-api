<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

class TimeField extends AbstractTemporalField {
    protected function getRegex() {
        return '/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/';
    }
}
