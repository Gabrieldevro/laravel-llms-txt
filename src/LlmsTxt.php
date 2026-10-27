<?php

namespace Gabrieldev\LlmsTxt;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

/**
 * Builds llms.txt (https://llmstxt.org) and llms-full.txt.
 *
 *   LlmsTxt::section('Docs', [Link::make('Install', '/docs/install', 'How to install')]);
 *   LlmsTxt::section('Blog', fn () => Post::latest()->take(30)->get()->map(
 *       fn ($p) => Link::make($p->title, route('posts.show', $p), $p->excerpt)->content(fn () => $p->body)
 *   ));
 */
class LlmsTxt
{
    /** @var array<string, list<Closure|iterable<Link>>> */
    private array $sections = [];

    private ?string $title = null;

    private ?string $summary = null;

    private ?string $details = null;

    public function __construct(private readonly Config $config, private readonly Router $router) {}

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function summary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function details(string $markdown): static
    {
        $this->details = $markdown;

        return $this;
    }

    /**
     * Adds links to a section. Pass a closure to build them lazily (only when the file is generated).
     * Calling it again with the same name appends to the section.
     *
     * @param  Closure(): iterable<Link>|iterable<Link>  $links
     */
    public function section(string $name, Closure|iterable $links): static
    {
        $this->sections[$name][] = $links;

        return $this;
    }

    /** Links an assistant may skip when its context window is short. */
    public function optional(Closure|iterable $links): static
    {
        return $this->section('Optional', $links);
    }

    public function forget(): static
    {
        $this->sections = [];
        $this->title = $this->summary = $this->details = null;

        return $this;
    }

    public function toText(): string
    {
        return $this->render(full: false);
    }

    public function toFullText(): string
    {
        return $this->render(full: true);
    }

    private function render(bool $full): string
    {
        $out = ['# '.$this->resolveTitle()];

        if (filled($summary = $this->summary ?? $this->config->get('llms-txt.summary'))) {
            $out[] = '> '.trim(preg_replace('/\s+/u', ' ', (string) $summary) ?? '');
        }
        if (filled($details = $this->details ?? $this->config->get('llms-txt.details'))) {
            $out[] = trim((string) $details);
        }

        $sections = $this->resolveSections();
        // Per the spec the "Optional" section goes last.
        if (isset($sections['Optional'])) {
            $optional = $sections['Optional'];
            unset($sections['Optional']);
            $sections['Optional'] = $optional;
        }

        foreach ($sections as $name => $links) {
            if ($links === []) {
                continue;
            }
            $out[] = "## {$name}\n\n".implode("\n", array_map(fn (Link $l) => $l->toMarkdown(), $links));

            if ($full) {
                foreach ($links as $link) {
                    if (($content = $link->resolveContent()) !== null) {
                        $out[] = "### {$link->title}\n\nSource: {$link->absoluteUrl()}\n\n".$this->limit($content);
                    }
                }
            }
        }

        return implode("\n\n", $out)."\n";
    }

    /** @return array<string, list<Link>> */
    private function resolveSections(): array
    {
        $sections = [];

        foreach ((array) $this->config->get('llms-txt.sections', []) as $name => $links) {
            foreach ($links as $link) {
                $sections[$name][] = $link instanceof Link ? $link : Link::make(...array_values((array) $link));
            }
        }

        foreach ((array) $this->config->get('llms-txt.routes', []) as $name => $patterns) {
            foreach ($this->router->getRoutes()->getRoutes() as $route) {
                $routeName = $route->getName();
                if ($routeName && in_array('GET', $route->methods(), true) && ! str_contains($route->uri(), '{') && Str::is((array) $patterns, $routeName)) {
                    $sections[$name][] = Link::make(Str::headline(Str::afterLast($routeName, '.')), '/'.ltrim($route->uri(), '/'));
                }
            }
        }

        foreach ($this->sections as $name => $groups) {
            foreach ($groups as $links) {
                foreach ($links instanceof Closure ? $links() : $links as $link) {
                    $sections[$name][] = $link;
                }
            }
        }

        // The same URL twice in one section is noise.
        return array_map(fn (array $links) => array_values(collect($links)->unique(fn (Link $l) => $l->absoluteUrl())->all()), $sections);
    }

    private function resolveTitle(): string
    {
        return (string) ($this->title ?? $this->config->get('llms-txt.title') ?? $this->config->get('app.name', 'Laravel'));
    }

    private function limit(string $content): string
    {
        $max = $this->config->get('llms-txt.full_max_chars');

        return $max && mb_strlen($content) > $max ? rtrim(mb_substr($content, 0, (int) $max))."\n\n[…]" : $content;
    }
}
