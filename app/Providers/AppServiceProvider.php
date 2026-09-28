<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12));

        /*
         * Money is checked on the server as well as hidden in the page, so a
         * typed-in address cannot show what the screen would not.
         */
        Gate::define('see-financials', fn (User $user): bool => $user->canSeeFinancials());
        Gate::define('handle-quotes', fn (User $user): bool => $user->canHandleQuotes());
    }
}
