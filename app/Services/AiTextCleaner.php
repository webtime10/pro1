<?php

namespace App\Services;

/**
 * Очистка текста из Gemini / markdown / «грязного» HTML перед сохранением в блог.
 */
class AiTextCleaner
{
    private const ALLOWED_TAGS = '<p><br><br/><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><blockquote><pre><code>';

    public function clean(?string $input): string
    {
        $text = (string) $input;
        if ($text === '') {
            return '';
        }

        $text = $this->stripInvisible($text);
        $text = $this->unwrapCodeFences($text);

        if ($this->looksLikeHtml($text)) {
            $text = $this->cleanHtml($text);
        } else {
            $text = $this->markdownToHtml($text);
        }

        $text = $this->normalizeQuotes($text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        $text = preg_replace('/(<p>\s*<br\s*\/?>\s*<\/p>\s*){2,}/iu', '<p><br></p>', $text) ?? $text;

        return trim($text);
    }

    /** Короткие поля: название, meta — без HTML. */
    public function cleanPlain(?string $input): string
    {
        $text = html_entity_decode(strip_tags((string) $input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = $this->stripInvisible($text);
        $text = $this->normalizeQuotes($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function stripInvisible(string $text): string
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $text) ?? $text;

        return $text;
    }

    private function unwrapCodeFences(string $text): string
    {
        if (preg_match('/^```(?:html|markdown|md|text)?\s*\n([\s\S]*?)\n```$/u', trim($text), $m)) {
            return trim($m[1]);
        }

        return preg_replace('/^```(?:html|markdown|md|text)?\s*\n?/u', '', preg_replace('/\n?```$/u', '', trim($text)) ?? $text) ?? $text;
    }

    private function looksLikeHtml(string $text): bool
    {
        return (bool) preg_match('/<\/?(p|div|br|h[1-6]|ul|ol|li|strong|em|a)\b/i', $text);
    }

    private function cleanHtml(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/\s(style|class|id|dir|lang|data-[\w-]+)=("[^"]*"|\'[^\']*\')/iu', '', $html) ?? $html;
        $html = preg_replace('/<\/?(span|font|div|section|article|header|footer)\b[^>]*>/iu', '', $html) ?? $html;
        $html = str_ireplace(['&nbsp;', '&#160;'], ' ', $html);
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/<a\b(?![^>]*\bhref=)/iu', '<a href="#"', $html) ?? $html;

        return $html;
    }

    private function markdownToHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $html = [];
        $para = [];
        $listOpen = false;
        $currentList = 'ul';

        $flushPara = function () use (&$para, &$html) {
            if ($para === []) {
                return;
            }
            $chunk = trim(implode(' ', $para));
            if ($chunk !== '') {
                $html[] = '<p>'.$this->inlineMarkdown($chunk).'</p>';
            }
            $para = [];
        };

        foreach ($lines as $line) {
            $trim = trim($line);

            if ($trim === '') {
                if ($listOpen) {
                    $html[] = '</'.$currentList.'>';
                    $listOpen = false;
                }
                $flushPara();
                continue;
            }

            if (preg_match('/^(#{1,4})\s+(.+)$/u', $trim, $m)) {
                if ($listOpen) {
                    $html[] = '</'.$currentList.'>';
                    $listOpen = false;
                }
                $flushPara();
                $level = min(4, max(2, strlen($m[1]) + 1));
                $html[] = '<h'.$level.'>'.$this->inlineMarkdown($m[2]).'</h'.$level.'>';
                continue;
            }

            if (preg_match('/^[-*+]\s+(.+)$/u', $trim, $m)) {
                $flushPara();
                if (! $listOpen || $currentList !== 'ul') {
                    if ($listOpen) {
                        $html[] = '</'.$currentList.'>';
                    }
                    $currentList = 'ul';
                    $html[] = '<ul>';
                    $listOpen = true;
                }
                $html[] = '<li>'.$this->inlineMarkdown($m[1]).'</li>';
                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.+)$/u', $trim, $m)) {
                $flushPara();
                if (! $listOpen || $currentList !== 'ol') {
                    if ($listOpen) {
                        $html[] = '</'.$currentList.'>';
                    }
                    $currentList = 'ol';
                    $html[] = '<ol>';
                    $listOpen = true;
                }
                $html[] = '<li>'.$this->inlineMarkdown($m[1]).'</li>';
                continue;
            }

            if ($listOpen) {
                $html[] = '</'.$currentList.'>';
                $listOpen = false;
            }
            $para[] = $trim;
        }

        if ($listOpen) {
            $html[] = '</'.$currentList.'>';
        }
        $flushPara();

        return implode("\n", $html);
    }

    private function inlineMarkdown(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/__(.+?)__/u', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/u', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u', '<a href="$2">$1</a>', $text) ?? $text;

        return $text;
    }

    private function normalizeQuotes(string $text): string
    {
        return str_replace(
            ['“', '”', '„', '«', '»', '‘', '’', '–', '—', '…'],
            ['"', '"', '"', '"', '"', "'", "'", '-', '-', '...'],
            $text
        );
    }
}
