<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Sign-in with Google (OAuth 2.0 via laravel/socialite). The callback URL is fixed (no locale prefix),
 * because it has to match the one registered in Google Cloud Console.
 */
class GoogleController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(Request $request): SymfonyRedirect
    {
        $locale = array_key_exists($request->query('locale'), config('app.locales')) ? $request->query('locale') : config('app.locale');
        $request->session()->put('auth.locale', $locale);

        abort_unless(self::enabled(), 404);

        return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $locale = $request->session()->pull('auth.locale', config('app.locale'));
        app()->setLocale($locale);

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login', ['locale' => $locale])->withErrors(['email' => __('site.auth.google_failed')]);
        }

        $email = Str::lower((string) $google->getEmail());

        if ($email === '') {
            return redirect()->route('login', ['locale' => $locale])->withErrors(['email' => __('site.auth.google_no_email')]);
        }

        // Existing Google account, or an e-mail account to link (Google has verified the address).
        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $email)->first()
            ?? new User(['email' => $email]);

        $user->fill([
            'name' => $user->name ?: ($google->getName() ?: Str::before($email, '@')),
            'google_id' => $google->getId(),
            'avatar' => $google->getAvatar(),
        ]);
        $user->email_verified_at ??= now();
        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('account', ['locale' => $locale]));
    }
}
