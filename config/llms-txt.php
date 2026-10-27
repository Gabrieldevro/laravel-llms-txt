<?php

return [
    // H1 of the file. Defaults to config('app.name').
    'title' => env('LLMS_TXT_TITLE'),

    // One-line summary shown as the blockquote under the title (the most important line for AI assistants).
    'summary' => env('LLMS_TXT_SUMMARY'),

    // Optional free text (markdown) after the summary: who you are, what you sell, where you deliver, contact...
    'details' => null,

    /*
     * Static sections. Each link: [title, url (absolute or relative), optional description].
     * A section named "Optional" is, per the spec, content an assistant may skip when its context is short.
     */
    'sections' => [
        // 'Main pages' => [
        //     ['About us', '/about', 'Who we are and how we work'],
        //     ['Pricing', '/pricing'],
        // ],
    ],

    // Expose a page for each named route matching these patterns (title = route name, prettified).
    'routes' => [
        // 'Pages' => ['pages.*'],
    ],

    'serve' => [
        'enabled' => env('LLMS_TXT_SERVE', true),
        // No session or cookies needed for a plain text file; add e.g. 'cache.headers' or your locale middleware.
        'middleware' => [],
        // Seconds to cache the generated text (0 = build on every request).
        'cache_ttl' => (int) env('LLMS_TXT_CACHE_TTL', 3600),
    ],

    // Maximum number of characters per page in llms-full.txt (null = no limit).
    'full_max_chars' => 20000,
];
