<?php

namespace Gabrieldev\LlmsTxt\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Gabrieldev\LlmsTxt\LlmsTxt title(string $title)
 * @method static \Gabrieldev\LlmsTxt\LlmsTxt summary(string $summary)
 * @method static \Gabrieldev\LlmsTxt\LlmsTxt details(string $markdown)
 * @method static \Gabrieldev\LlmsTxt\LlmsTxt section(string $name, \Closure|iterable $links)
 * @method static \Gabrieldev\LlmsTxt\LlmsTxt optional(\Closure|iterable $links)
 * @method static \Gabrieldev\LlmsTxt\LlmsTxt forget()
 * @method static string toText()
 * @method static string toFullText()
 *
 * @see \Gabrieldev\LlmsTxt\LlmsTxt
 */
class LlmsTxt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Gabrieldev\LlmsTxt\LlmsTxt::class;
    }
}
