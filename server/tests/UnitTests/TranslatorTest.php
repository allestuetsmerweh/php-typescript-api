<?php

declare(strict_types=1);

namespace PhpTypeScriptApi\Tests\UnitTests;

use PhpTypeScriptApi\Tests\UnitTests\Common\UnitTestCase;
use PhpTypeScriptApi\Translator;

/**
 * @internal
 *
 * @coversNothing
 */
class TranslatorForTest extends Translator {
    protected static ?string $previous_lang_path = null;

    /** @return array<string> */
    public function testOnlyGetAcceptLangs(): array {
        return $this->accept_langs;
    }

    /** @return array<string> */
    public function testOnlyGetFallbackChain(): array {
        return $this->fallback_chain;
    }

    public static function testOnlySetLangPath(string $test_lang_path): void {
        self::$previous_lang_path = self::$lang_path;
        self::$lang_path = $test_lang_path;
    }

    public static function testOnlyResetLangPath(): void {
        self::$lang_path = self::$previous_lang_path;
    }

    /**
     * @return array<string, string|array<string, string|array<string, string>>>
     */
    protected function readMessagesJson(string $lang): array {
        if ($lang === 'de_ch') {
            return [
                'bike' => "Velo",
                'very' => [
                    'deep' => [
                        'path' => "Sehr tüüfe Pfad",
                    ],
                ],
            ];
        }
        if ($lang === 'de') {
            return [
                'bike' => "Fahrrad",
                'taste' => "schmecken",
            ];
        }
        if ($lang === 'en') {
            return [
                'bike' => "bike",
                'taste' => "taste",
                'supercali' => "Supercalifragilisticexpialidocious",
            ];
        }
        throw new \Exception("Unexpected lang");
    }

    public static function resetInstance(): void {
        self::$instance = null;
    }
}

/**
 * @internal
 *
 * @covers \PhpTypeScriptApi\Translator
 */
final class TranslatorTest extends UnitTestCase {
    public function testSetAcceptLangs(): void {
        $translator = new TranslatorForTest();
        $translator->setAcceptLangs('de-CH,de;q=0.8,en-US;q=0.5,en;q=0.3');
        $this->assertSame(
            ['de_ch', 'de', 'en_us', 'en'],
            $translator->testOnlyGetAcceptLangs()
        );
        $this->assertSame(
            ['en'],
            $translator->getProjectLangs()
        );
        $this->assertSame(
            ['en'],
            $translator->testOnlyGetFallbackChain()
        );
    }

    public function testReadProjectLangs(): void {
        $translator = new TranslatorForTest();
        $translator->readProjectLangs();
        $this->assertSame(
            ['de', 'en'],
            $translator->getProjectLangs()
        );
        $this->assertSame(
            ['en'],
            $translator->testOnlyGetAcceptLangs()
        );
        $this->assertSame(
            ['en'],
            $translator->testOnlyGetFallbackChain()
        );
    }

    public function testReadBrokenProjectLangs(): void {
        TranslatorForTest::testOnlySetLangPath(__FILE__);
        $translator = new TranslatorForTest();
        $translator->readProjectLangs();
        $this->assertSame(
            ['en'],
            $translator->getProjectLangs()
        );
        $this->assertSame(
            ['en'],
            $translator->testOnlyGetAcceptLangs()
        );
        $this->assertSame(
            ['en'],
            $translator->testOnlyGetFallbackChain()
        );
        TranslatorForTest::testOnlyResetLangPath();
    }

    public function testCalculateFallbackChain(): void {
        $translator = new TranslatorForTest();
        $translator->readProjectLangs();
        $translator->setAcceptLangs('de-CH,de;q=0.8,en-US;q=0.5,en;q=0.3');
        $this->assertSame(
            ['de', 'en'],
            $translator->getProjectLangs()
        );
        $this->assertSame(
            ['de_ch', 'de', 'en_us', 'en'],
            $translator->testOnlyGetAcceptLangs()
        );
        $this->assertSame(
            ['de', 'en'],
            $translator->testOnlyGetFallbackChain()
        );
    }

    public function testTrans(): void {
        $translator = new TranslatorForTest();
        $translator->setProjectLangs(['de_ch', 'de', 'en']);
        $translator->setAcceptLangs('de-CH,de;q=0.8,en-US;q=0.5,en;q=0.3');
        $this->assertSame(
            ['de_ch', 'de', 'en'],
            $translator->getProjectLangs()
        );
        $this->assertSame(
            ['de_ch', 'de', 'en_us', 'en'],
            $translator->testOnlyGetAcceptLangs()
        );
        $this->assertSame(
            ['de_ch', 'de', 'en'],
            $translator->testOnlyGetFallbackChain()
        );
        $this->assertSame("Velo", $translator->trans('bike'));
        $this->assertSame("schmecken", $translator->trans('taste'));
        $this->assertSame(
            "Supercalifragilisticexpialidocious",
            $translator->trans('supercali')
        );
        $this->assertSame("", $translator->trans('no_such_translation'));
        $this->assertSame("Sehr tüüfe Pfad", $translator->trans('very.deep.path'));
    }

    public function testDoubleUnderscore(): void {
        TranslatorForTest::resetInstance();
        $this->assertSame(
            "Illegible integer: pi",
            TranslatorForTest::__('fields.illegible_integer', [
                'value' => 'pi',
            ])
        );
    }

    public function testGetInstance(): void {
        TranslatorForTest::resetInstance();
        $instance1 = TranslatorForTest::getInstance();
        $instance1->setProjectLangs(['de_ch', 'de', 'en']);
        $instance2 = TranslatorForTest::getInstance();
        $this->assertSame(
            ['de_ch', 'de', 'en'],
            $instance2->getProjectLangs()
        );
        $this->assertSame($instance1, $instance2);
    }

    public function testRealTranslation(): void {
        TranslatorForTest::resetInstance();
        $translator = TranslatorForTest::getInstance();
        $translator->setAcceptLangs('de-CH,de;q=0.8,en-US;q=0.5,en;q=0.3');
        $this->assertSame(
            "Unlesbare Ganzzahl: pi",
            $translator->trans('fields.illegible_integer', [
                'value' => 'pi',
            ])
        );
    }

    public function testRealBrokenTranslation(): void {
        TranslatorForTest::testOnlySetLangPath(__FILE__);
        TranslatorForTest::resetInstance();
        $translator = TranslatorForTest::getInstance();
        $translator->setAcceptLangs('de-CH,de;q=0.8,en-US;q=0.5,en;q=0.3');
        $this->assertSame(
            '',
            $translator->trans('fields.illegible_integer', [
                'value' => 'pi',
            ])
        );
        TranslatorForTest::testOnlyResetLangPath();
    }
}
