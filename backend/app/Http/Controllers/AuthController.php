<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Login local com email e senha (somente quando `DEV_PASSWORD_LOGIN=true`).
     * Retorna token Sanctum para uso no header `Authorization: Bearer`.
     */
    public function devLogin(Request $request): JsonResponse
    {
        abort_unless(config('dayflow.dev_password_login'), 404);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (! $this->validateEmailDomain($user->email)) {
            throw ValidationException::withMessages([
                'email' => [__('Unauthorized domain.')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => [__('Account inactive.')],
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('dev-login')->plainTextToken;
        $user->load('role', 'manager');

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
            'status' => 'success',
        ]);
    }

    /**
     * Login do superadmin (credenciais em config/dayflow.php ou .env).
     */
    public function superadminLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = config('dayflow.superadmin.email');
        $password = config('dayflow.superadmin.password');

        if (
            $validated['email'] !== $email
            || $validated['password'] !== $password
        ) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! $user->is_active) {
            abort(503, 'Superadmin user not found. Run: php artisan migrate --seed');
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('superadmin')->plainTextToken;
        $user->load('role', 'manager');

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
            'status' => 'success',
        ]);
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(): \Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['profile', 'email'])
            ->stateless()
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(): \Illuminate\Http\RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Validate domain
            if (!$this->validateEmailDomain($googleUser->email)) {
                Log::warning("Unauthorized domain login attempt: {$googleUser->email}");
                return redirect(env('FRONTEND_URL') . '/auth/error?reason=invalid_domain');
            }

            // Find or create user
            $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                $user = User::where('email', $googleUser->email)->first();
                
                if (!$user) {
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                        'is_active' => true,
                    ]);

                    // Assign default role if needed
                    if (!$user->role_id) {
                        $defaultRole = \App\Models\Role::where('slug', 'user')->first();
                        if ($defaultRole) {
                            $user->role_id = $defaultRole->id;
                            $user->save();
                        }
                    }
                } else {
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                    ]);
                }
            }

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Generate token
            $token = $user->createToken('api-token')->plainTextToken;

            // Redirect with token
            $redirectUrl = env('FRONTEND_URL') . '/auth/callback?token=' . $token . '&user=' . urlencode(json_encode($user));

            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL') . '/auth/error?reason=auth_failed');
        }
    }

    /**
     * Validate email domain
     */
    private function validateEmailDomain(string $email): bool
    {
        $allowedDomains = explode(',', env('ALLOWED_EMAIL_DOMAINS', '@uello.com.br'));
        
        foreach ($allowedDomains as $domain) {
            if (str_ends_with($email, trim($domain))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role', 'manager', 'subordinates');

        return response()->json([
            'data' => $user,
            'status' => 'success',
        ]);
    }

    /**
     * Logout (revoke all tokens)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
            'status' => 'success',
        ]);
    }
}
