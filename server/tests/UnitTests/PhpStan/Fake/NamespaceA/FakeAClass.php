<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA;

use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\FakeClass as FakeTopLevelClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\NamespaceAA\FakeAAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceB\FakeBClass;

/**
 * @extends FakeAnotherAClass<FakeAAClass<FakeBClass<FakeTopLevelClass<string>>>>
 */
class FakeAClass extends FakeAnotherAClass {
}
