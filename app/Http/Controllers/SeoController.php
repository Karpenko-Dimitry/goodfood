<?php

namespace App\Http\Controllers;

use App\Models\Diet;
use App\Models\Dish;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Crawler-facing files: sitemap.xml (Google, Bing), robots.txt and llms.txt (AI assistants).
 */
class SeoController extends Controller
{
    /** AI crawlers and assistants we explicitly welcome (training, search and live browsing). */
    public const AI_BOTS = [
        'GPTBot', 'OAI-SearchBot', 'ChatGPT-User',
        'ClaudeBot', 'Claude-SearchBot', 'Claude-User',
        'PerplexityBot', 'Perplexity-User',
        'Google-Extended', 'Applebot-Extended', 'Meta-ExternalAgent', 'Amazonbot', 'CCBot',
    ];

    private const DISALLOW = ['/admin', '/livewire/', '/*/ai-plan/'];   // admin, internals, personal AI plans

    public function robots(): Response
    {
        $rules = collect(self::DISALLOW)->map(fn ($path) => "Disallow: {$path}")->implode("\n");

        $body = "User-agent: *\nAllow: /\n{$rules}\n\n"
            ."# AI assistants are welcome to read and cite DietCoach.\n"
            .collect(self::AI_BOTS)->map(fn ($bot) => "User-agent: {$bot}")->implode("\n")
            ."\nAllow: /\n{$rules}\n\n"
            .'Sitemap: '.url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap.'.request()->getHost(), now()->addHour(), function () {
            $entries = collect([
                ['home', [], null, 'daily', '1.0', []],
                ['diets.index', [], null, 'weekly', '0.9', []],
                ['dishes.index', [], null, 'daily', '0.9', []],
                ['plan.create', [], null, 'monthly', '0.8', []],
                ['about', [], null, 'yearly', '0.4', []],
                ['contact', [], null, 'yearly', '0.3', []],
            ]);

            foreach (Diet::published()->get() as $diet) {
                $entries->push(['diets.show', ['diet' => $diet], $diet->updated_at, 'monthly', '0.8', [$diet->imageUrl('lg')]]);
            }

            foreach (Dish::published()->latest('id')->get() as $dish) {
                $entries->push(['dishes.show', ['dish' => $dish], $dish->updated_at, 'monthly', '0.7', [$dish->imageUrl('lg')]]);
            }

            return view('seo.sitemap', ['entries' => $entries, 'locales' => array_keys(config('app.locales'))])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * llms.txt (https://llmstxt.org): a concise Markdown map of the site for AI assistants.
     */
    public function llms(): Response
    {
        $body = Cache::remember('seo.llms.'.request()->getHost(), now()->addHour(), function () {
            app()->setLocale('en');   // one language for AI; pages exist in uk / ru / en

            return view('seo.llms', [
                'diets' => Diet::published()->get(),
                'dishes' => Dish::published()->with('category', 'diets')->orderBy('dish_category_id')->get(),
                'locales' => config('app.locales'),
            ])->render();
        });

        return response($body, 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }
}
