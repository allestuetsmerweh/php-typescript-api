<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\PhpStan;

enum ValidationDirection {
    case Serialize;
    case Deserialize;
}
