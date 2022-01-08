<?php

namespace PhpTypeScriptApi;

class Translator {
    protected static $instance;

    public const LANG_PATH = __DIR__.'/../../resources/lang/';

    protected $project_langs_dict = ['en' => true];
    protected $accept_langs = ['en'];
    protected $fallback_chain = ['en'];

    public function readProjectLangs() {
        $entries = scandir($this::LANG_PATH);
        $langs = array_filter($entries, function ($entry) {
            $lang_path = $this::LANG_PATH;
            return
                $entry !== '.' && $entry !== '..'
                && is_file("{$lang_path}{$entry}/messages.json")
            ;
        });
        $this->setProjectLangs($langs);
    }

    public function getProjectLangs() {
        return array_keys($this->project_langs_dict);
    }

    public function setProjectLangs($langs) {
        $langs_dict = [];
        foreach ($langs as $lang) {
            $langs_dict[$lang] = true;
        }
        $this->project_langs_dict = $langs_dict;
        $this->calculateFallbackChain();
    }

    public function setAcceptLangs($accept_lang_str) {
        $raw_accept_langs = explode(',', $accept_lang_str);
        $accept_langs = array_map(function ($raw_accept_lang) {
            $lang = explode(';', $raw_accept_lang)[0];
            return trim(str_replace('-', '_', strtolower($lang)));
        }, $raw_accept_langs);
        $this->accept_langs = $accept_langs;
        $this->calculateFallbackChain();
    }

    protected function calculateFallbackChain() {
        $fallback_chain = [];
        foreach ($this->accept_langs as $accept_lang) {
            $is_project_lang = $this->project_langs_dict[$accept_lang] ?? false;
            if ($is_project_lang) {
                $fallback_chain[] = $accept_lang;
            }
        }
        $this->fallback_chain = $fallback_chain;
    }

    public function trans(?string $id, array $parameters = [], string $domain = null, string $locale = null) {
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
        return msgfmt_format_message($lang, $message, $parameters);
    }

    protected function getLangMessage(?string $id) {
        $id_parts = explode('.', $id);
        foreach ($this->fallback_chain as $lang) {
            $messages = $this->readMessagesJson($lang);
            if (isset($messages[$id])) {
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

    protected function readMessagesJson($lang) {
        $lang_path = $this::LANG_PATH;
        $messages_json_path = "{$lang_path}{$lang}/messages.json";
        return json_decode(file_get_contents($messages_json_path), true) ?? [];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            $instance = new self();
            $instance->readProjectLangs();
            self::$instance = $instance;
        }
        return self::$instance;
    }
}
