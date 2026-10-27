<?php

namespace Gabrieldev\LlmsTxt\Support;

/**
 * Small, dependency-free HTML → Markdown converter for page bodies (headings, paragraphs, lists, links, emphasis,
 * code, tables flattened to lines). Plain text and markdown pass through untouched.
 */
final class HtmlToMarkdown
{
    public static function convert(string $html): string
    {
        if ($html === strip_tags($html)) {
            return trim($html);
        }

        // Drop things an assistant never needs.
        $html = preg_replace('#<(script|style|noscript|svg|iframe|form|nav|footer)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<!--.*?-->#s', '', $html) ?? $html;

        $rules = [
            '#<h([1-6])\b[^>]*>(.*?)</h\1>#is' => fn ($m) => "\n\n".str_repeat('#', max(2, (int) $m[1])).' '.self::inline($m[2])."\n\n",
            '#<pre\b[^>]*>(.*?)</pre>#is' => fn ($m) => "\n\n```\n".html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')."\n```\n\n",
            '#<li\b[^>]*>(.*?)</li>#is' => fn ($m) => "\n- ".self::inline($m[1]),
            '#<blockquote\b[^>]*>(.*?)</blockquote>#is' => fn ($m) => "\n\n> ".self::inline($m[1])."\n\n",
            '#<tr\b[^>]*>(.*?)</tr>#is' => fn ($m) => "\n- ".implode(' | ', array_map([self::class, 'inline'], preg_split('#</t[dh]>#i', $m[1]) ?: [])),
            '#<(p|div|section|article|ul|ol|table|br)\b[^>]*/?>#i' => fn () => "\n\n",
        ];
        foreach ($rules as $pattern => $replace) {
            $html = preg_replace_callback($pattern, $replace, $html) ?? $html;
        }

        $text = self::inline($html);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = preg_replace("/(\n- [^\n]*)\n\n(?=- )/", "$1\n", $text) ?? $text;

        return trim(preg_replace('/[ \t]*\|[ \t]*$/m', '', $text) ?? $text);
    }

    /** Inline formatting inside a block. */
    private static function inline(string $html): string
    {
        $html = preg_replace_callback('#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', function ($m) {
            $label = trim(strip_tags($m[2]));

            return $label === '' || str_starts_with($m[1], '#') || str_starts_with($m[1], 'javascript:') ? $label : "[{$label}]({$m[1]})";
        }, $html) ?? $html;
        $html = preg_replace('#<(strong|b)\b[^>]*>(.*?)</\1>#is', '**$2**', $html) ?? $html;
        $html = preg_replace('#<(em|i)\b[^>]*>(.*?)</\1>#is', '*$2*', $html) ?? $html;
        $html = preg_replace('#<code\b[^>]*>(.*?)</code>#is', '`$1`', $html) ?? $html;
        $html = preg_replace_callback('#<img\b[^>]*alt=["\']([^"\']+)["\'][^>]*>#i', fn ($m) => '['.$m[1].']', $html) ?? $html;

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text, " \t");
    }
}
