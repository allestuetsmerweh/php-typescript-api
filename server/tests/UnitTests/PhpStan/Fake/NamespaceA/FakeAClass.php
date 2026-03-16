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
 * @phpstan-import-type FakeTopType from FakeTopLevelClass
 * @phpstan-import-type FakeSameFileAType from FakeSameFileAClass
 *
 * @phpstan-type FakeType array{
 *   anotherA: FakeAnotherAType,
 *   aa: FakeAAType,
 *   b: FakeBType,
 *   top: FakeTopType,
 *   sameFileA: FakeSameFileAType,
 * }
 * 
 * @extends FakeAnotherAClass<FakeAnotherAType,
 *   FakeAAClass<FakeAAType,
 *     FakeBClass<FakeBType,
 *       FakeTopLevelClass<FakeTopType,
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
