<?php

namespace Gabrieldev\LlmsTxt;

use Closure;
use Gabrieldev\LlmsTxt\Support\HtmlToMarkdown;

final class Link
{
    /** @var (Closure(): ?string)|string|null */
    private Closure|string|null $content = null;

    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $description = null,
    ) {}

    public static function make(string $title, string $url, ?string $description = null): self
    {
        return new self($title, $url, $description);
    }

    /**
     * Full text for llms-full.txt: markdown, HTML (converted), or a closure resolved only when llms-full.txt is built.
     *
     * @param  (Closure(): ?string)|string|null  $content
     */
    public function content(Closure|string|null $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function resolveContent(): ?string
    {
        $content = $this->content instanceof Closure ? ($this->content)() : $this->content;

        return blank($content) ? null : HtmlToMarkdown::convert((string) $content);
    }

    public function absoluteUrl(): string
    {
        return preg_match('#^https?://#i', $this->url) ? $this->url : url($this->url);
    }

    /** "- [Pricing](https://example.com/pricing): Plans and prices" */
    public function toMarkdown(): string
    {
        $title = str_replace(['[', ']'], ['\[', '\]'], trim($this->title));
        $line = "- [{$title}]({$this->absoluteUrl()})";

        $description = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->description)) ?? '');

        return $description === '' ? $line : "{$line}: {$description}";
    }
}
