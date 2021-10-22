<?php

require_once __DIR__.'/AbstractTemporalField.php';

class TimeField extends AbstractTemporalField {
    protected function getRegex() {
        return '/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/';
    }
}
