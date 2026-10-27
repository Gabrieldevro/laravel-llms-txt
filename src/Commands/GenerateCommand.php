<?php

namespace Gabrieldev\LlmsTxt\Commands;

use Gabrieldev\LlmsTxt\LlmsTxt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateCommand extends Command
{
    protected $signature = 'llms-txt:generate
        {--path= : Directory to write llms.txt and llms-full.txt to (default: public/)}
        {--clear : Only clear the cached files served by the routes}';

    protected $description = 'Write static llms.txt and llms-full.txt files (or clear the cached ones)';

    public function handle(LlmsTxt $llms): int
    {
        foreach (['index', 'full'] as $type) {
            Cache::forget("llms-txt:{$type}:".app()->getLocale());
        }

        if ($this->option('clear')) {
            $this->components->info('llms.txt cache cleared.');

            return self::SUCCESS;
        }

        $dir = rtrim($this->option('path') ?: public_path(), '/\\');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            $this->components->error("Cannot create {$dir}");

            return self::FAILURE;
        }

        foreach (['llms.txt' => $llms->toText(), 'llms-full.txt' => $llms->toFullText()] as $file => $text) {
            file_put_contents("{$dir}/{$file}", $text);
            $this->components->twoColumnDetail($file, number_format(strlen($text)).' bytes');
        }

        if ($dir === rtrim(public_path(), '/\\') && config('llms-txt.serve.enabled', true)) {
            $this->components->warn('Static files in public/ take precedence over the routes; delete them to serve live content again.');
        }

        return self::SUCCESS;
    }
}
