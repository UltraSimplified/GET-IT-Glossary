<?php

namespace App\Providers;

use App\Models\TermEditor;
use App\Models\User;
use App\Support\CurrentSite;
use App\Themes\ThemeRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ThemeRegistry::class);

        // The site and its theme, resolved once for each request.
        $this->app->scoped(CurrentSite::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureErrorPages();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Who may do what. Anyone can register, so every management screen is
     * guarded here rather than by hiding its menu link.
     */
    protected function configureAuthorization(): void
    {
        // Users, pages and creating terms.
        Gate::define('admin', fn (User $user): bool => (bool) $user->is_admin);

        // Adding images to term and page content.
        Gate::define('upload', fn (User $user): bool => (bool) $user->is_admin
            || TermEditor::query()->where('user_id', $user->id)->exists());
    }

    /**
     * Show "not found" and similar errors inside the site instead of on a
     * bare error screen. Server errors keep Laravel's debug page locally.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            $status = $response->statusCode();

            if (in_array($status, [403, 404], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'))) {
                return $response->render('Error', ['status' => $status, 'letter' => null])->withSharedData();
            }

            return null;
        });
    }
}
