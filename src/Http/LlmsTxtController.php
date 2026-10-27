<?php

namespace Gabrieldev\LlmsTxt\Http;

use Gabrieldev\LlmsTxt\LlmsTxt;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class LlmsTxtController
{
    public function index(LlmsTxt $llms): Response
    {
        return $this->respond('llms-txt:index:'.app()->getLocale(), fn () => $llms->toText());
    }

    public function full(LlmsTxt $llms): Response
    {
        return $this->respond('llms-txt:full:'.app()->getLocale(), fn () => $llms->toFullText());
    }

    private function respond(string $key, \Closure $build): Response
    {
        $ttl = (int) config('llms-txt.serve.cache_ttl', 3600);
        $text = $ttl > 0 ? Cache::remember($key, $ttl, $build) : $build();

        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'public, max-age='.max(0, $ttl),
        ]);
    }
}
