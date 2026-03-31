<?php

namespace App\Providers;

use App\Models\Enquiry;
use App\Models\Form;
use App\Models\Note;
use App\Models\Reply;
use App\Policies\EnquiryPolicy;
use App\Policies\FormPolicy;
use App\Policies\InsightsPolicy;
use App\Policies\NotePolicy;
use App\Policies\ReplyPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Form::class, FormPolicy::class);
        Gate::policy(Enquiry::class, EnquiryPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(Reply::class, ReplyPolicy::class);

        Gate::define('insights.view-account', [InsightsPolicy::class, 'viewForAccount']);

        RateLimiter::for('form-submissions', function (Request $request) {
            $token = (string) $request->route('token');

            return Limit::perMinute(5)->by($token . '|' . $request->ip());
        });
    }
}
