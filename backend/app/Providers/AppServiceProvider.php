<?php

namespace App\Providers;

use App\Models\Cargo;
use App\Models\Team;
use App\Models\User;
use App\Models\VacationRequest;
use App\Policies\CargoPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use App\Policies\VacationRequestPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->mergeGoogleOAuthFromEnvFileIfMisconfigured();

        Gate::policy(VacationRequest::class, VacationRequestPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Cargo::class, CargoPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);

        if (class_exists(\Laravel\Sanctum\Sanctum::class)) {
            \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\Laravel\Sanctum\PersonalAccessToken::class);
        }

        Scramble::afterOpenApiGenerated(function (\Dedoc\Scramble\Support\Generator\OpenApi $openApi): void {
            $openApi->components->addSecurityScheme(
                'sanctum',
                SecurityScheme::http('bearer')->setDescription('Laravel Sanctum personal access token (Authorization: Bearer)')
            );
        });
    }

    /**
     * Laravel Dotenv uses safeLoad(): existing OS env vars are not overwritten by .env.
     * An empty GOOGLE_CLIENT_ID in the environment (e.g. stale Docker compose) leaves Socialite
     * without a client_id. Parse .env directly when config is blank but the file has values.
     */
    private function mergeGoogleOAuthFromEnvFileIfMisconfigured(): void
    {
        if (! blank(config('services.google.client_id'))) {
            return;
        }

        $path = base_path('.env');
        if (! is_readable($path)) {
            return;
        }

        try {
            $parsed = \Dotenv\Dotenv::parse((string) file_get_contents($path));
        } catch (\Throwable) {
            return;
        }

        $clientId = $parsed['GOOGLE_CLIENT_ID'] ?? null;
        if (blank($clientId)) {
            return;
        }

        config([
            'services.google.client_id' => $clientId,
            'services.google.client_secret' => $parsed['GOOGLE_CLIENT_SECRET'] ?? config('services.google.client_secret'),
            'services.google.redirect' => $parsed['GOOGLE_REDIRECT_URI']
                ?? $parsed['GOOGLE_REDIRECT_URL']
                ?? config('services.google.redirect'),
        ]);
    }
}
