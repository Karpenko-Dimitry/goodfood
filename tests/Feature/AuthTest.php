<?php

namespace Tests\Feature;

use App\Ai\Testing\FakeAi;
use App\Models\DietPlanRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        FakeAi::install($this->app);
        config(['services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret']);
    }

    public function test_register_login_logout(): void
    {
        $this->get('/uk/register')->assertOk()->assertSee(__('site.auth.google', [], 'uk'));

        $this->post('/uk/register', [
            'name' => 'Олена', 'email' => ' Olena@Example.com ', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertRedirect('/uk/account');

        $user = User::where('email', 'olena@example.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);

        $this->get('/uk/account')->assertOk()->assertSee('Вітаємо, Олена!');
        $this->post('/uk/logout')->assertRedirect('/uk');
        $this->assertGuest();

        $this->post('/en/login', ['email' => 'olena@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/en/login', ['email' => 'OLENA@example.com', 'password' => 'secret-pass'])->assertRedirect('/en/account');
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_validation(): void
    {
        $this->from('/ru/register')->post('/ru/register', [
            'name' => '', 'email' => 'admin@dietcoach.test', 'password' => 'short', 'password_confirmation' => 'other',
        ])->assertRedirect('/ru/register')->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertSame(1, User::count());
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/uk/login', ['email' => 'admin@dietcoach.test', 'password' => 'wrong']);
        }

        $this->post('/uk/login', ['email' => 'admin@dietcoach.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Забагато спроб входу', session('errors')->first('email'));   // even with the right password
        $this->assertGuest();
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        // Same answer for unknown e-mails (no account probing).
        $this->post('/uk/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status');
        $this->post('/uk/forgot-password', ['email' => 'reset@example.com'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token, $user) {
            $token = $n->token;
            $mail = $n->toMail($user);

            return $n->locale === 'uk'
                && str_starts_with($mail->actionUrl, url('/uk/reset-password/'.$n->token))
                && $mail->subject === 'Відновлення пароля DietCoach';
        });

        $this->get("/uk/reset-password/{$token}?email=reset@example.com")->assertOk();
        $this->post('/uk/reset-password', [
            'token' => $token, 'email' => 'reset@example.com', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect('/uk/account');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(\Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_google_sign_in_creates_and_links_accounts(): void
    {
        $this->get('/auth/google/redirect?locale=en')->assertRedirect();
        $this->assertSame('en', session('auth.locale'));

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-123', 'name' => 'Ivan Petrenko', 'email' => 'Ivan@Gmail.com', 'avatar' => 'https://lh3.googleusercontent.com/a/x',
        ]));

        $this->withSession(['auth.locale' => 'en'])->get('/auth/google/callback')->assertRedirect('/en/account');

        $user = User::where('google_id', 'google-123')->sole();
        $this->assertSame('ivan@gmail.com', $user->email);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->is_admin);
        $this->assertAuthenticatedAs($user);

        // An existing e-mail account is linked, not duplicated.
        auth()->logout();
        $existing = User::factory()->create(['email' => 'mary@example.com', 'name' => 'Mary']);
        Socialite::fake('google', (new SocialiteUser)->map(['id' => 'google-456', 'name' => 'M', 'email' => 'mary@example.com', 'avatar' => null]));
        $this->withSession(['auth.locale' => 'uk'])->get('/auth/google/callback')->assertRedirect('/uk/account');

        $this->assertSame('google-456', $existing->fresh()->google_id);
        $this->assertSame('Mary', $existing->fresh()->name);
        $this->assertSame(1, User::where('email', 'mary@example.com')->count());
    }

    public function test_google_button_hidden_when_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/uk/login')->assertOk()->assertDontSee('/auth/google/redirect');
        $this->get('/auth/google/redirect')->assertNotFound();
    }

    public function test_only_admins_can_open_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->actingAs(User::where('email', 'admin@dietcoach.test')->first())->get('/admin/users')->assertOk();
    }

    public function test_admin_changes_a_user_password_and_signs_them_out(): void
    {
        config(['session.driver' => 'database']);
        $admin = User::where('email', 'admin@dietcoach.test')->first();
        $user = User::factory()->create(['email' => 'client@example.com', 'password' => 'old-password']);
        \DB::table('sessions')->insert(['id' => 'client-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $rememberToken = $user->remember_token;

        $this->actingAs($admin);
        $page = fn () => \Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class);

        $page()->callTableAction('changePassword', $user, ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertHasTableActionErrors(['password']);
        $page()->callTableAction('changePassword', $user, ['password' => 'new-password-1', 'password_confirmation' => 'mismatch'])
            ->assertHasTableActionErrors(['password']);

        $page()->callTableAction('changePassword', $user, ['password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Пароль изменён');

        $user->refresh();
        $this->assertTrue(\Hash::check('new-password-1', $user->password));
        $this->assertNotSame($rememberToken, $user->remember_token);
        $this->assertFalse(\DB::table('sessions')->where('id', 'client-session')->exists());

        auth()->logout();
        $this->post('/uk/login', ['email' => 'client@example.com', 'password' => 'new-password-1'])->assertRedirect('/uk/account');
    }

    public function test_admin_changing_own_password_stays_signed_in(): void
    {
        $admin = User::where('email', 'admin@dietcoach.test')->first();
        $this->actingAs($admin)->get('/admin/users')->assertOk();   // AuthenticateSession stores the password hash

        \Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->callTableAction('changePassword', $admin, ['password' => 'my-new-pass', 'password_confirmation' => 'my-new-pass'])
            ->assertHasNoTableActionErrors();

        $this->get('/admin/users')->assertOk();
        $this->assertTrue(\Hash::check('my-new-pass', $admin->fresh()->password));
    }

    public function test_admin_edits_a_user(): void
    {
        $admin = User::where('email', 'admin@dietcoach.test')->first();
        $user = User::factory()->create(['name' => 'Old', 'email' => 'old@example.com', 'email_verified_at' => now()]);
        DietPlanRequest::create(['user_id' => $user->id, 'locale' => 'uk', 'gender' => 'male', 'age' => 30, 'height_cm' => 180,
            'weight_kg' => 80, 'activity' => 'light', 'goal' => 'gain', 'status' => 'completed', 'result' => ['daily_calories' => 2900]]);
        $edit = fn (User $record) => \Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\EditUser::class, ['record' => $record->getRouteKey()]);

        $this->actingAs($admin);
        $this->get(\App\Filament\Resources\Users\UserResource::getUrl('edit', ['record' => $user]))->assertOk();

        $edit($user)->assertFormSet(['name' => 'Old', 'email' => 'old@example.com', 'is_admin' => false])
            ->fillForm(['email' => 'admin@dietcoach.test'])->call('save')
            ->assertHasFormErrors(['email' => 'unique']);

        $edit($user)->fillForm(['name' => 'New Name', 'email' => 'New@Example.COM', 'is_admin' => true])
            ->call('save')->assertHasNoFormErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue($user->is_admin);              // not mass assignable, saved explicitly
        $this->assertNull($user->email_verified_at);     // e-mail changed

        // Own account: the admin toggle is locked.
        $edit($admin)->assertFormFieldIsDisabled('is_admin')->fillForm(['name' => 'Boss'])->call('save')->assertHasNoFormErrors();
        $this->assertTrue($admin->fresh()->is_admin);
        $this->assertSame('Boss', $admin->fresh()->name);

        // The user's AI plans.
        \Livewire\Livewire::test(\App\Filament\Resources\Users\RelationManagers\DietPlansRelationManager::class, [
            'ownerRecord' => $user, 'pageClass' => \App\Filament\Resources\Users\Pages\EditUser::class,
        ])->assertCanSeeTableRecords($user->dietPlans)->assertSee('2900');
    }

    public function test_admin_role_change_in_the_list_requires_confirmation(): void
    {
        $admin = User::where('email', 'admin@dietcoach.test')->first();
        $user = User::factory()->create();
        $this->actingAs($admin);
        $list = fn () => \Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class);

        // Clicking opens a confirmation modal; nothing changes until it is confirmed.
        $mounted = $list()->mountTableAction('toggleAdmin', $user)->instance()->getMountedAction();
        $this->assertSame('toggleAdmin', $mounted->getName());
        $this->assertTrue($mounted->isConfirmationRequired());
        $this->assertSame('Сделать администратором?', $mounted->getModalHeading());
        $this->assertFalse($user->fresh()->is_admin);

        $list()->callTableAction('toggleAdmin', $user)->assertNotified('Права администратора выданы');
        $this->assertTrue($user->fresh()->is_admin);

        $list()->callTableAction('toggleAdmin', $user->fresh())->assertNotified('Права администратора сняты');
        $this->assertFalse($user->fresh()->is_admin);

        // Not available on your own account.
        $list()->assertTableActionHidden('toggleAdmin', $admin);
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_non_admin_cannot_change_passwords(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_guests_are_sent_to_localized_login_and_users_away_from_it(): void
    {
        $this->get('/en/account')->assertRedirect('/en/login');
        $this->actingAs(User::factory()->create())->get('/ru/login')->assertRedirect('/ru/account');
    }

    public function test_plans_are_linked_to_the_signed_in_user(): void
    {
        $user = User::factory()->create(['name' => 'Taras']);
        $this->actingAs($user);

        $this->get('/uk/ai-plan')->assertSee('value="Taras"', false);

        $this->post('/uk/ai-plan', [
            'gender' => 'male', 'age' => 35, 'height_cm' => 180, 'weight_kg' => 85,
            'activity' => 'moderate', 'goal' => 'lose', 'meals_per_day' => 4,
        ]);

        $this->assertSame($user->id, DietPlanRequest::sole()->user_id);
        $this->get('/uk/account')->assertSee(__('site.plan.goal_lose', [], 'uk'));
    }
}
