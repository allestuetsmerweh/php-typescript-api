<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA;

use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\FakeClass as FakeTopLevelClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceA\NamespaceAA\FakeAAClass;
use PhpTypeScriptApi\Tests\UnitTests\PhpStan\Fake\NamespaceB\FakeBClass;

/**
 * @phpstan-import-type FakeAnotherAType from FakeAnotherAClass
 * @phpstan-import-type FakeAAType from FakeAAClass
 * @phpstan-import-type FakeBType from FakeBClass
 * @phpstan-import-type FakeType from FakeTopLevelClass
 * @phpstan-import-type FakeSameFileAType from FakeSameFileAClass
 *
 * @extends FakeAnotherAClass<FakeAnotherAType,
 *   FakeAAClass<FakeAAType,
 *     FakeBClass<FakeBType,
 *       FakeTopLevelClass<FakeType,
 *         FakeSameFileAClass<FakeSameFileAType,
 *           string>>>>>
 */
class FakeAClass extends FakeAnotherAClass {
}

/**
 * @template T
 * @template U
 *
 * @phpstan-type FakeSameFileAType string
 */
class FakeSameFileAClass {
}
