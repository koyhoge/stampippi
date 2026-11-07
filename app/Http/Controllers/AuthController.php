<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        $devUsers = User::isDevToolsEnabled()
            ? User::orderBy('id')->get()
            : collect();

        return view('auth.login', compact('devUsers'));
    }

    public function devLogin(Request $request): RedirectResponse
    {
        abort_unless(User::isDevToolsEnabled(), 403, '現在、開発用ログイン機能は無効化されています。');

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('status', "開発用ログイン：{$user->name} (@{$user->handle}) としてログインしました。");
    }

    public function redirectToGoogle(): RedirectResponse
    {
        if (empty(config('services.google.client_id'))) {
            return redirect()->route('login')->withErrors([
                'login' => '.env に GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET が設定されていません。ローカル環境では「開発用ワンクリックログイン」をご利用ください。',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'login' => 'Google 認証に失敗しました: ' . $e->getMessage(),
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar() ?: $user->avatar_url,
            ]);
        } else {
            $baseHandle = Str::slug(Str::before($googleUser->getEmail(), '@'), '_');
            if (empty($baseHandle)) {
                $baseHandle = 'user';
            }
            $handle = $baseHandle;
            $counter = 1;
            while (User::where('handle', $handle)->exists()) {
                $handle = $baseHandle . '_' . $counter++;
            }

            $user = User::create([
                'handle' => strtolower($handle),
                'name' => $googleUser->getName() ?: $handle,
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);
        }

        $user->syncInitialAdminStatus(
            'google',
            null,
            $googleUser->getEmail(),
            (string) $googleUser->getId()
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Googleアカウントでログインしました。');
    }

    public function redirectToGithub(): RedirectResponse
    {
        if (empty(config('services.github.client_id'))) {
            return redirect()->route('login')->withErrors([
                'login' => '.env に GITHUB_CLIENT_ID / GITHUB_CLIENT_SECRET が設定されていません。ローカル環境では「開発用ワンクリックログイン」をご利用ください。',
            ]);
        }

        return Socialite::driver('github')->redirect();
    }

    public function handleGithubCallback(Request $request): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'login' => 'GitHub 認証に失敗しました: ' . $e->getMessage(),
            ]);
        }

        $githubId = (string) $githubUser->getId();
        $email = $githubUser->getEmail();

        $user = User::where('github_id', $githubId)
            ->when($email, fn ($query) => $query->orWhere('email', $email))
            ->first();

        if ($user) {
            $user->update([
                'github_id' => $githubId,
                'avatar_url' => $githubUser->getAvatar() ?: $user->avatar_url,
            ]);
        } else {
            $rawHandle = $githubUser->getNickname()
                ?: ($email ? Str::before($email, '@') : 'github_user');
            $baseHandle = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $rawHandle));
            $baseHandle = substr(trim($baseHandle, '_'), 0, 26) ?: 'user';

            $handle = $baseHandle;
            $counter = 1;
            while (User::where('handle', $handle)->exists()) {
                $handle = $baseHandle . '_' . $counter++;
            }

            $resolvedEmail = $email ?: ($handle . '@users.noreply.github.com');

            $user = User::create([
                'handle' => $handle,
                'name' => $githubUser->getName() ?: ($githubUser->getNickname() ?: $handle),
                'email' => strtolower($resolvedEmail),
                'github_id' => $githubId,
                'avatar_url' => $githubUser->getAvatar(),
            ]);
        }

        $user->syncInitialAdminStatus(
            'github',
            $githubUser->getNickname(),
            $githubUser->getEmail(),
            $githubId
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('status', 'GitHubアカウントでログインしました。');
    }

    public function editProfile(Request $request): View
    {
        return view('auth.profile', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'handle' => [
                'required',
                'string',
                'max:32',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('users', 'handle')->ignore($user->id),
            ],
            'name' => ['required', 'string', 'max:80'],
        ]);

        $user->update([
            'handle' => strtolower($validated['handle']),
            'name' => $validated['name'],
        ]);

        return redirect()->route('profile.edit')->with('status', 'プロフィール（@handle・表示名）を更新しました。');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'ログアウトしました。');
    }
}

