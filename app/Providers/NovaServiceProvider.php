<?php

namespace App\Providers;

use App\Models\AgendaItem;
use App\Models\Partner;
use App\Models\Speaker;
use App\Models\User;
use App\Nova as Resources;
use App\Nova\Auth\UpdateUserPassword;
use App\Nova\Dashboards\AgendaOverview;
use App\Nova\Dashboards\ContentOverview;
use App\Nova\Dashboards\Editorial;
use App\Nova\Dashboards\Main;
use App\Nova\Lenses\BusiestSpeakers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Features;
use Laravel\Nova\Dashboard;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Laravel\Nova\Tool;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // The conference runs in Warsaw: show agenda times in Warsaw time for every editor.
        Nova::userTimezone(fn () => 'Europe/Warsaw');

        Nova::withBreadcrumbs();

        // Plain-JS Vue components (no build step); Nova only loads scripts for signed-in users.
        Nova::script('sortable-card', resource_path('js/nova/sortable-card.js'));
        Nova::script('speaker-spotlight-card', resource_path('js/nova/speaker-spotlight-card.js'));

        Nova::mainMenu(fn (Request $request) => [
            MenuSection::dashboard(Main::class)->icon('chart-bar'),
            MenuSection::dashboard(Editorial::class)->icon('clipboard-document-check'),

            MenuSection::make('Agenda', [
                MenuItem::dashboard(AgendaOverview::class),
                MenuItem::resource(Resources\AgendaItem::class)->withBadge(fn () => AgendaItem::count()),
                MenuItem::resource(Resources\Stage::class),
                MenuItem::resource(Resources\Tag::class),
            ])->icon('calendar-days')->collapsable(),

            MenuSection::make('Content', [
                MenuItem::dashboard(ContentOverview::class),
                MenuItem::resource(Resources\Speaker::class)->withBadge(fn () => Speaker::count()),
                MenuItem::lens(Resources\Speaker::class, BusiestSpeakers::class),
                MenuItem::resource(Resources\Partner::class)->withBadge(fn () => Partner::count()),
                MenuItem::resource(Resources\Faq::class),
            ])->icon('document-text')->collapsable(),

            MenuSection::make('Admin', [
                MenuItem::resource(Resources\User::class),
            ])->icon('users')->collapsable(),
        ]);

        Nova::userMenu(fn (Request $request, Menu $menu) => $menu->prepend(
            MenuItem::externalLink('View wawtech.io', 'https://wawtech.io')->openInNewTab(),
        ));

        Nova::footer(fn () => '<p class="text-center">WAWTech Demo · Built with Laravel Nova</p>');
    }

    /**
     * Register the configurations for Laravel Fortify.
     */
    protected function fortify(): void
    {
        Nova::fortify()
            ->features([
                Features::updatePasswords(),
                // Features::emailVerification(),
                // Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
                // Features::passkeys(),
            ])
            ->register();

        // Read-only accounts (the public demo) must not change the password everyone shares.
        // Nova re-binds its password updater on every request, so wrap whatever is bound
        // instead of binding over it.
        $this->app->extend(UpdatesUserPasswords::class, fn () => new UpdateUserPassword);
    }

    /**
     * Register the Nova routes.
     */
    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes(default: true)
            ->withPasswordResetRoutes()
            ->withoutEmailVerificationRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function (User $user) {
            // Nova already admits everyone locally; mirroring that here lets impersonation work in the demo.
            // Elsewhere only the emails in NOVA_ADMIN_EMAILS get in, plus read-only accounts,
            // which ContentPolicy and UserPolicy stop from changing anything.
            return app()->isLocal()
                || $user->is_read_only
                || in_array(strtolower($user->email), config('nova.admin_emails'), true);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array<int, Dashboard>
     */
    protected function dashboards(): array
    {
        return [
            new Main,
            new Editorial,
            new AgendaOverview,
            new ContentOverview,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return [];
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        //
    }
}
