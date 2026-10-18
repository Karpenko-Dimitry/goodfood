<?php

namespace Tests\Feature;

use App\Ai\Testing\FakeAi;
use App\Models\Diet;
use App\Models\DietPlanRequest;
use App\Models\Dish;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        FakeAi::install($this->app);
    }

    public function test_every_page_has_complete_head(): void
    {
        $dish = Dish::where('slug', 'salmon-quinoa-bowl')->first();
        $diet = Diet::where('slug', 'keto')->first();

        foreach (['/uk', '/ru/diets', '/en/recipes', "/uk/diets/{$diet->slug}", "/en/recipes/{$dish->slug}", '/uk/ai-plan', '/en/about', '/ru/contact'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            foreach (['<link rel="canonical" href="'.url($path).'">', 'property="og:title"', 'property="og:image"', 'property="og:url" content="'.url($path).'"',
                'name="twitter:card" content="summary_large_image"', 'hreflang="x-default"', 'name="robots" content="index, follow', '"@type":"WebSite"'] as $needle) {
                $this->assertStringContainsString($needle, $html, "{$path}: {$needle}");
            }

            preg_match('~<meta name="description" content="([^"]*)">~', $html, $m);
            $this->assertNotEmpty($m[1] ?? '', "{$path}: description");
            $this->assertLessThanOrEqual(163, mb_strlen(html_entity_decode($m[1])), "{$path}: description length");
            $this->assertSame(1, substr_count($html, '<title>'));
        }
    }

    public function test_favicons_are_linked_and_present(): void
    {
        $html = $this->get('/uk')->assertOk()->getContent();

        foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'site.webmanifest'] as $file) {
            $this->assertStringContainsString(asset($file), $html);
            $this->assertFileExists(public_path($file));
            $this->assertGreaterThan(0, filesize(public_path($file)), $file);
        }

        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest['icons'] as $icon) {
            [$width] = getimagesize(public_path(ltrim($icon['src'], '/')));
            $this->assertSame((int) explode('x', $icon['sizes'])[0], $width, $icon['src']);
        }
    }

    public function test_recipe_page_has_valid_recipe_schema(): void
    {
        $dish = Dish::where('slug', 'salmon-quinoa-bowl')->first();
        $html = $this->get("/en/recipes/{$dish->slug}")->assertOk()->getContent();

        $schemas = $this->jsonLd($html);
        $recipe = collect($schemas)->firstWhere('@type', 'Recipe');

        $this->assertSame('Salmon & Quinoa Bowl', $recipe['name']);
        $this->assertSame('PT15M', $recipe['prepTime']);
        $this->assertSame('PT35M', $recipe['totalTime']);
        $this->assertSame('560 kcal', $recipe['nutrition']['calories']);
        $this->assertSame('36 g', $recipe['nutrition']['proteinContent']);
        $this->assertCount(7, $recipe['recipeIngredient']);
        $this->assertSame('HowToStep', $recipe['recipeInstructions'][0]['@type']);
        $this->assertNotEmpty($recipe['image']);

        $crumbs = collect($schemas)->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(['Home', 'Recipes', 'Lunch', 'Salmon & Quinoa Bowl'], array_column($crumbs['itemListElement'], 'name'));
        $this->assertStringContainsString('property="og:type" content="article"', $html);
    }

    public function test_diet_page_and_lists_have_schema(): void
    {
        $diet = Diet::where('slug', 'keto')->first();
        $article = collect($this->jsonLd($this->get("/uk/diets/{$diet->slug}")->getContent()))->firstWhere('@type', 'Article');
        $this->assertSame('Кето-дієта', $article['headline']);

        $list = collect($this->jsonLd($this->get('/en/diets')->getContent()))->firstWhere('@type', 'ItemList');
        $this->assertSame(Diet::published()->count(), count($list['itemListElement']));
    }

    public function test_private_and_duplicate_pages_are_not_indexed(): void
    {
        $plan = DietPlanRequest::create([
            'locale' => 'uk', 'gender' => 'male', 'age' => 40, 'height_cm' => 180, 'weight_kg' => 90,
            'activity' => 'light', 'goal' => 'lose', 'status' => 'pending',
        ]);

        $this->get("/uk/ai-plan/{$plan->uuid}")->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get('/uk/recipes?category=breakfast')->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('<link rel="canonical" href="'.url('/uk/recipes').'">', false);
        $this->get('/uk/recipes?page=2')->assertSee('<link rel="canonical" href="'.url('/uk/recipes').'?page=2">', false);
    }

    public function test_sitemap_lists_all_pages_in_every_language(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);

        $static = 6;
        $expected = ($static + Diet::published()->count() + Dish::published()->count()) * count(config('app.locales'));
        $this->assertCount($expected, $xml->url);

        $content = $response->getContent();
        $this->assertStringContainsString('<loc>'.url('/en/recipes/salmon-quinoa-bowl').'</loc>', $content);
        $this->assertStringContainsString('hreflang="uk" href="'.url('/uk/recipes/salmon-quinoa-bowl').'"', $content);
        $this->assertStringContainsString('<image:loc>', $content);
        $this->assertStringNotContainsString('/ai-plan/', $content);
    }

    public function test_robots_txt_allows_ai_crawlers_and_points_to_sitemap(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();

        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
        foreach (['GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended'] as $bot) {
            $this->assertStringContainsString("User-agent: {$bot}", $body);
        }
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /*/ai-plan/', $body);
    }

    public function test_llms_txt_summarizes_the_site_for_ai(): void
    {
        $body = $this->get('/llms.txt')->assertOk()->getContent();

        $this->assertStringStartsWith('# DietCoach', $body);
        $this->assertStringContainsString('[Keto Diet]('.url('/en/diets/keto').')', $body);
        $this->assertStringContainsString('[Salmon & Quinoa Bowl]('.url('/en/recipes/salmon-quinoa-bowl').'): Lunch; 560 kcal, protein 36 g', $body);
        $this->assertStringContainsString('## Tools', $body);
    }

    /** @return list<array> */
    private function jsonLd(string $html): array
    {
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);

        return array_map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $m[1]);
    }
}
