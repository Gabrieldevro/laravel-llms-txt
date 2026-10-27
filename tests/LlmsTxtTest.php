<?php

namespace Gabrieldev\LlmsTxt\Tests;

use Gabrieldev\LlmsTxt\Facades\LlmsTxt;
use Gabrieldev\LlmsTxt\Link;
use Gabrieldev\LlmsTxt\LlmsTxtServiceProvider;
use Gabrieldev\LlmsTxt\Support\HtmlToMarkdown;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class LlmsTxtTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LlmsTxtServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', 'https://shop.test');
        $app['url']->forceRootUrl('https://shop.test');
        $app['url']->forceScheme('https');
        $app['config']->set('app.name', 'Bebe Shop');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('llms-txt.summary', 'Toys and baby gear, delivered in Romania in 3–7 days.');
        $app['config']->set('llms-txt.sections', [
            'Main pages' => [
                ['About us', '/about', 'Who we are'],
                ['Shipping', 'https://shop.test/shipping'],
            ],
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/contact', fn () => 'contact')->name('pages.contact');
        $router->get('/terms-and-conditions', fn () => 'terms')->name('pages.terms-and-conditions');
        $router->get('/products/{slug}', fn () => 'p')->name('pages.product');
        $router->post('/newsletter', fn () => 'n')->name('pages.newsletter');
    }

    public function test_it_renders_the_spec_format(): void
    {
        LlmsTxt::section('Products', [
            Link::make('LEGO City Police [Station]', '/products/lego-city', "Building set,\n 6+ years"),
        ]);

        $text = LlmsTxt::toText();

        $this->assertStringStartsWith("# Bebe Shop\n\n> Toys and baby gear, delivered in Romania in 3–7 days.\n\n## Main pages\n\n", $text);
        $this->assertStringContainsString('- [About us](https://shop.test/about): Who we are', $text);
        $this->assertStringContainsString("- [Shipping](https://shop.test/shipping)\n", $text);
        $this->assertStringContainsString('- [LEGO City Police \[Station\]](https://shop.test/products/lego-city): Building set, 6+ years', $text);
    }

    public function test_optional_section_is_always_last_and_duplicates_are_removed(): void
    {
        LlmsTxt::optional([Link::make('Careers', '/careers')]);
        LlmsTxt::section('Blog', [Link::make('Post', '/blog/post'), Link::make('Same post', '/blog/post')]);

        $text = LlmsTxt::toText();

        $this->assertGreaterThan(strpos($text, '## Blog'), strpos($text, '## Optional'));
        $this->assertSame(1, substr_count($text, '/blog/post'));
    }

    public function test_sections_from_named_routes_skip_parameters_and_non_get(): void
    {
        config(['llms-txt.routes' => ['Pages' => ['pages.*']]]);

        $text = LlmsTxt::toText();

        $this->assertStringContainsString('- [Contact](https://shop.test/contact)', $text);
        $this->assertStringContainsString('- [Terms And Conditions](https://shop.test/terms-and-conditions)', $text);
        $this->assertStringNotContainsString('{slug}', $text);
        $this->assertStringNotContainsString('newsletter', $text);
    }

    public function test_lazy_sections_and_full_content(): void
    {
        $built = 0;
        LlmsTxt::section('Guides', function () use (&$built) {
            $built++;

            return [Link::make('Car seat guide', '/guides/car-seats', 'How to choose')
                ->content('<h1>Choosing a car seat</h1><p>Look for <strong>i-Size</strong>.</p><ul><li>0–15 months</li><li>15–36 months</li></ul><script>x()</script>')];
        });

        $this->assertSame(0, $built);
        $index = LlmsTxt::toText();
        $full = LlmsTxt::toFullText();

        $this->assertStringNotContainsString('i-Size', $index);
        $this->assertStringContainsString("### Car seat guide\n\nSource: https://shop.test/guides/car-seats\n\n## Choosing a car seat\n\nLook for **i-Size**.\n\n- 0–15 months\n- 15–36 months", $full);
        $this->assertStringNotContainsString('x()', $full);
    }

    public function test_routes_serve_plain_text(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('# Bebe Shop', false);

        $this->get('/llms-full.txt')->assertOk();
    }

    public function test_generate_command_writes_static_files(): void
    {
        $dir = sys_get_temp_dir().'/llms-txt-'.uniqid();

        $this->artisan('llms-txt:generate', ['--path' => $dir])->assertSuccessful();

        $this->assertStringStartsWith('# Bebe Shop', file_get_contents("{$dir}/llms.txt"));
        $this->assertFileExists("{$dir}/llms-full.txt");
        array_map('unlink', glob("{$dir}/*"));
        rmdir($dir);
    }

    public function test_html_to_markdown(): void
    {
        $md = HtmlToMarkdown::convert('<p>See <a href="https://x.test/a">the docs</a> and <em>enjoy</em> &amp; share.</p><table><tr><th>Size</th><th>Age</th></tr><tr><td>S</td><td>0–3</td></tr></table><pre><code>composer require x</code></pre>');

        $this->assertStringContainsString('See [the docs](https://x.test/a) and *enjoy* & share.', $md);
        $this->assertStringContainsString("- Size | Age\n- S | 0–3", $md);
        $this->assertStringContainsString("```\ncomposer require x\n```", $md);
        $this->assertSame('already **markdown**', HtmlToMarkdown::convert('already **markdown**'));
    }
}
