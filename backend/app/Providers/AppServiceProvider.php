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
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dotenv\Dotenv;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

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

        Gate::define('viewApiDocs', fn ($user = null) => in_array(app()->environment(), ['local', 'testing']) || (bool) config('scramble.docs_enabled', false));

        Scramble::configure()->routes(function (Route $route) {
            $prefix = config('scramble.api_path', 'api');
            $expectedDomain = config('scramble.api_domain');
            $matchesBase = ! $prefix || Str::startsWith($route->uri, $prefix);
            $domainOk = ! $expectedDomain || $route->getDomain() === $expectedDomain;

            if (! $matchesBase || ! $domainOk) {
                return false;
            }

            // Exclui o fallback JSON do api.php (não é operação real da API).
            return ! Str::contains($route->uri, '{fallbackPlaceholder}');
        });

        if (class_exists(Sanctum::class)) {
            Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        }

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->secure(
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
            $parsed = Dotenv::parse((string) file_get_contents($path));
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
