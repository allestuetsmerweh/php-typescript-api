<?php

namespace PhpTypeScriptApi;

class Translator {
    protected static ?Translator $instance = null;

    protected static string $lang_path = __DIR__.'/../../resources/lang/';

    /** @var array<string, bool> */
    protected array $project_langs_dict = ['en' => true];
    /** @var array<string> */
    protected array $accept_langs = ['en'];
    /** @var array<string> */
    protected array $fallback_chain = ['en'];

    public function readProjectLangs(): void {
        $entries = scandir($this::$lang_path);
        if (!$entries) {
            return;
        }
        $langs = array_filter($entries, function ($entry) {
            $lang_path = $this::$lang_path;
            return
                $entry !== '.' && $entry !== '..'
                && is_file("{$lang_path}{$entry}/messages.json");
        });
        $this->setProjectLangs($langs);
    }

    /**
     * @return array<string>
     */
    public function getProjectLangs(): array {
        return array_keys($this->project_langs_dict);
    }

    /**
     * @param array<string> $langs
     */
    public function setProjectLangs(array $langs): void {
        $langs_dict = [];
        foreach ($langs as $lang) {
            $langs_dict[$lang] = true;
        }
        $this->project_langs_dict = $langs_dict;
        $this->calculateFallbackChain();
    }

    public function setAcceptLangs(?string $accept_lang_str): void {
        $raw_accept_langs = explode(',', $accept_lang_str ?? '');
        $accept_langs = array_map(function ($raw_accept_lang) {
            $lang = explode(';', $raw_accept_lang)[0];
            return trim(str_replace('-', '_', strtolower($lang)));
        }, $raw_accept_langs);
        $this->accept_langs = $accept_langs;
        $this->calculateFallbackChain();
    }

    protected function calculateFallbackChain(): void {
        $fallback_chain = [];
        foreach ($this->accept_langs as $accept_lang) {
            $is_project_lang = $this->project_langs_dict[$accept_lang] ?? false;
            if ($is_project_lang) {
                $fallback_chain[] = $accept_lang;
            }
        }
        $this->fallback_chain = $fallback_chain;
    }

    /**
     * @param array<string, string> $parameters
     */
    public function trans(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        $lang_message = $this->getLangMessage($id);
        if (!$lang_message) {
            return '';
        }
        $lang = $lang_message[0];
        $message = $lang_message[1];
        if (!function_exists('msgfmt_format_message')) {
            // @codeCoverageIgnoreStart
            // Reason: Hard to test!
            return $message;
            // @codeCoverageIgnoreEnd
        }
        $result = msgfmt_format_message($lang, $message, $parameters);
        return $result ? $result : '';
    }

    /**
     * @return array<string>
     */
    protected function getLangMessage(string $id): ?array {
        $id_parts = explode('.', $id);
        foreach ($this->fallback_chain as $lang) {
            $messages = $this->readMessagesJson($lang);
            if (is_string($messages[$id] ?? null)) {
                return [$lang, $messages[$id]];
            }
            $message = $messages;
            foreach ($id_parts as $id_part) {
                $message = $message[$id_part] ?? [];
            }
            if (is_string($message)) {
                return [$lang, $message];
            }
        }
        return null;
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    protected function readMessagesJson(string $lang): array {
        $lang_path = $this::$lang_path;
        $messages_json_path = "{$lang_path}{$lang}/messages.json";
        $messages_json_content = file_get_contents($messages_json_path);
        if (!$messages_json_content) {
            return [];
        }
        return json_decode($messages_json_content, true) ?? [];
    }

    public static function getInstance(): Translator {
        if (self::$instance === null) {
            $instance = new self();
            $instance->readProjectLangs();
            self::$instance = $instance;
        }
        return self::$instance;
    }

    public static function resetInstance(): void {
        self::$instance = null;
    }

    /**
     * @param array<string, string> $parameters
     */
    public static function __(
        string $id,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null,
    ): string {
        $translator = self::getInstance();
        return $translator->trans($id, $parameters, $domain, $locale);
    }
}

// @codeCoverageIgnoreStart
// Reason: Functions can't be tested...
/**
 * @deprecated use Translator::__() instead
 *
 * @param array<string, string> $parameters
 */
function __(
    string $id,
    array $parameters = [],
    ?string $domain = null,
    ?string $locale = null,
): string {
    $translator = Translator::getInstance();
    return $translator->trans($id, $parameters, $domain, $locale);
}
// @codeCoverageIgnoreEnd
