<?php

namespace PhpTypeScriptApi\Fields\FieldTypes;

class DateField extends AbstractTemporalField {
    protected function getRegex(): string {
        return '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
    }
}
