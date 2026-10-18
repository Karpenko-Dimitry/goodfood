<?php

namespace Tests\Feature;

use App\Ai\Testing\FakeAi;
use App\Filament\Resources\Visitors\Pages\ListVisitors;
use App\Filament\Widgets\VisitorsOverview;
use App\Models\User;
use App\Models\Visitor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Mobile/15E148';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        FakeAi::install($this->app);
        $this->withHeader('User-Agent', self::BROWSER);
    }

    public function test_a_guest_is_recorded_once_and_followed_by_cookie(): void
    {
        $first = $this->get('/ru/diets', ['Referer' => 'https://www.google.com/search?q=diet'])->assertOk();

        $visitor = Visitor::sole();
        $first->assertCookie(config('visitors.cookie'), $visitor->visitor_id);
        $this->assertTrue($visitor->isGuest());
        $this->assertSame('mobile', $visitor->device);
        $this->assertSame('www.google.com', $visitor->referrer);
        $this->assertSame('/ru/diets', $visitor->landing_path);
        $this->assertTrue($visitor->first_seen_at->isToday());

        $this->withCookie(config('visitors.cookie'), $visitor->visitor_id)
            ->get('/en/recipes')->assertOk()->assertCookieMissing(config('visitors.cookie'));

        $visitor = Visitor::sole();
        $this->assertSame(2, $visitor->page_views);
        $this->assertSame('/en/recipes', $visitor->last_path);
        $this->assertSame('en', $visitor->locale);
    }

    public function test_ip_is_stored_anonymized(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])->get('/ru')->assertOk();

        $this->assertSame('203.0.113.0', Visitor::sole()->ip);
    }

    public function test_signed_in_user_is_linked_to_the_visitor(): void
    {
        $this->get('/ru')->assertOk();
        $visitor = Visitor::sole();

        $admin = User::first();
        $this->actingAs($admin)->withCookie(config('visitors.cookie'), $visitor->visitor_id)->get('/admin')->assertOk();

        $this->assertSame($admin->id, $visitor->fresh()->user_id);
        $this->assertSame(1, Visitor::registered()->count());
        $this->assertSame(0, Visitor::guests()->count());
    }

    public function test_bots_json_polling_and_errors_are_not_tracked(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)')->get('/ru')->assertOk();
        $this->withHeader('User-Agent', self::BROWSER)->getJson('/ru/diets')->assertOk();
        $this->get('/ru/diets/does-not-exist')->assertNotFound();
        $this->post('/ru/ai-plan', [])->assertRedirect();

        $this->assertSame(0, Visitor::count());
    }

    public function test_online_new_and_prune_scopes(): void
    {
        Visitor::create(['visitor_id' => fake()->uuid(), 'first_seen_at' => now()->subDays(3), 'last_seen_at' => now()->subMinute()]);
        Visitor::create(['visitor_id' => fake()->uuid(), 'first_seen_at' => now(), 'last_seen_at' => now()->subMinutes(30)]);
        Visitor::create(['visitor_id' => fake()->uuid(), 'first_seen_at' => now()->subDays(200), 'last_seen_at' => now()->subDays(120)]);

        $this->assertSame(1, Visitor::online()->count());
        $this->assertSame(1, Visitor::newSince(today())->count());

        $this->artisan('model:prune', ['--model' => [Visitor::class]])->assertSuccessful();
        $this->assertSame(2, Visitor::count());
    }

    public function test_admin_shows_visitors_and_live_counters(): void
    {
        $this->get('/ru')->assertOk();   // one online guest
        $this->actingAs(User::first());

        $this->get('/admin/visitors')->assertOk()->assertSee('Посетители');   // the admin is recorded too

        Livewire::test(ListVisitors::class)
            ->assertCanSeeTableRecords(Visitor::all())
            ->filterTable('online')
            ->assertCountTableRecords(2)
            ->filterTable('type', 'guests')
            ->assertCountTableRecords(1);

        Livewire::test(VisitorsOverview::class)
            ->assertSee('Онлайн сейчас')
            ->assertSee('пользователей: 1 · гостей: 1');
    }
}
