<h1 align="center">🤖 laravel-llms-txt</h1>

<p align="center"><b>Serve <code>/llms.txt</code> and <code>/llms-full.txt</code> from your Laravel app</b> so ChatGPT, Claude, Perplexity, Gemini and coding agents understand your site — built from config, routes or Eloquent models, always up to date.<br>
<i>Generează automat llms.txt pentru site-ul tău Laravel, ca asistenții AI să-ți înțeleagă și să-ți recomande site-ul.</i></p>

<p align="center">
<img alt="tests" src="https://github.com/Gabrieldevro/laravel-llms-txt/actions/workflows/tests.yml/badge.svg">
<img alt="Laravel" src="https://img.shields.io/badge/Laravel-12%20%7C%2013-FF2D20?style=flat-square&logo=laravel&logoColor=white">
<img alt="PHP" src="https://img.shields.io/badge/PHP-%E2%89%A58.2-777BB4?style=flat-square&logo=php&logoColor=white">
<img alt="spec" src="https://img.shields.io/badge/spec-llmstxt.org-black?style=flat-square">
<img alt="License" src="https://img.shields.io/badge/license-MIT-blue?style=flat-square">
</p>

---

## What is llms.txt?

[`llms.txt`](https://llmstxt.org) is a markdown file at the root of your site that tells large language models, in a few hundred tokens, **what your site is and which pages matter**. It is to AI assistants what `robots.txt` and `sitemap.xml` are to search engines. `llms-full.txt` goes further and inlines the content of those pages, so an agent can read your docs, policies or product guides in one request.

Search is moving into chat. If an assistant can't quickly understand your site, it recommends someone else's. This is the cheapest piece of **GEO (Generative Engine Optimization)** you can ship.

```markdown
# Bebe-Land

> Kids' store in Romania: toys, strollers and car seats, delivered in 3–7 working days, 14-day returns.

## Main pages

- [Shipping](https://bebe-land.com/livrare): Costs, carriers and delivery times
- [Returns](https://bebe-land.com/retur): 14-day returns, how to send a product back

## Guides

- [How to choose a car seat](https://bebe-land.com/ghid/scaun-auto): i-Size groups by age and height

## Optional

- [Careers](https://bebe-land.com/cariere)
```

## Features

- 📄 Spec-compliant output (H1, summary blockquote, H2 link sections, `Optional` always last).
- 🧩 Sections from **config**, from **named routes** (`pages.*`), or from **any query/collection** in code.
- 💤 Lazy: closures run only when the file is requested, then the result is **cached** (1h by default).
- 📚 **`/llms-full.txt`** with each page's content, converted from HTML to clean markdown (no dependencies).
- 🔗 Relative URLs become absolute, duplicates are removed, descriptions are flattened to one line.
- 🛠️ `php artisan llms-txt:generate` to write static files (for static hosting or CDN), `--clear` to bust the cache.
- 🌍 Locale-aware cache keys — combine with your locale middleware for multilingual sites.

## Installation

```bash
composer require gabrieldevro/laravel-llms-txt
php artisan vendor:publish --tag=llms-txt-config   # optional
```

That's it — `/llms.txt` and `/llms-full.txt` are live.

## Usage

### 1. Config only

```php
// config/llms-txt.php
'title' => 'Bebe-Land',
'summary' => 'Kids\' store in Romania: toys, strollers and car seats, delivered in 3–7 working days.',
'details' => "We ship only within Romania. Prices include all taxes. Support: contact@example.com",

'sections' => [
    'Main pages' => [
        ['Shipping', '/shipping', 'Costs, carriers and delivery times'],
        ['Returns', '/returns', '14-day returns'],
    ],
],

// every GET route named pages.* without parameters
'routes' => [
    'Company' => ['pages.*'],
],
```

### 2. From your models (in a service provider)

```php
use Gabrieldev\LlmsTxt\Facades\LlmsTxt;
use Gabrieldev\LlmsTxt\Link;

public function boot(): void
{
    LlmsTxt::section('Categories', fn () => Category::active()->orderBy('position')->get()
        ->map(fn ($c) => Link::make($c->name, route('category', $c), $c->meta_description)));

    LlmsTxt::section('Guides', fn () => Post::published()->latest()->take(30)->get()
        ->map(fn ($p) => Link::make($p->title, route('posts.show', $p), $p->excerpt)
            ->content(fn () => $p->body)));   // HTML or markdown → goes into llms-full.txt

    LlmsTxt::optional([Link::make('Careers', '/careers')]);
}
```

Closures are only executed when `/llms.txt` is generated, never on normal page loads.

### 3. Static files

```bash
php artisan llms-txt:generate              # writes public/llms.txt and public/llms-full.txt
php artisan llms-txt:generate --path=dist  # anywhere else
php artisan llms-txt:generate --clear      # just clear the cached response
```

Run `--clear` after publishing new content, or schedule it:

```php
Schedule::command('llms-txt:generate --clear')->hourly();
```

## Configuration

| Key | Default | |
|---|---|---|
| `title` | `app.name` | H1 |
| `summary` | `null` | the one-line blockquote — the most important line |
| `details` | `null` | free markdown under the summary |
| `sections` | `[]` | `['Section' => [[title, url, description?], …]]` |
| `routes` | `[]` | `['Section' => ['route.name.*']]` |
| `serve.enabled` | `true` | register the two routes |
| `serve.middleware` | `[]` | no session/cookies by default |
| `serve.cache_ttl` | `3600` | seconds, `0` = no cache |
| `full_max_chars` | `20000` | per page in llms-full.txt |

## Tips for a good llms.txt

- Write the **summary for a stranger**: what you sell or do, for whom, where, and your strongest differentiator.
- Link **evergreen, high-value pages** (pricing, shipping, returns, guides, docs, categories), not every product.
- Keep descriptions factual and short; assistants quote them.
- Put nice-to-have pages under `Optional`.

---

## 🇷🇴 Pe scurt, în română

`llms.txt` este „sitemap-ul pentru inteligența artificială”: un fișier markdown la rădăcina site-ului care le spune lui ChatGPT, Claude sau Perplexity ce face site-ul tău și care sunt paginile importante. Pachetul îl generează automat în Laravel:

- `/llms.txt` și `/llms-full.txt` (cu conținutul paginilor), gata după `composer require`;
- secțiuni din config, din rute sau din modele (categorii, articole, ghiduri), cu cache;
- comanda `php artisan llms-txt:generate` pentru fișiere statice.

Este cea mai simplă optimizare pentru căutarea prin AI (GEO).

## License

MIT © [Gabriel Dev](https://gabrieldev.eu) — Laravel, e-commerce & SEO.

If this helped you show up in AI answers, a ⭐ helps other developers find it.
