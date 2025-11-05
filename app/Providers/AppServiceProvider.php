<?php

namespace App\Providers;

use App\Models\ChatMessage;
use App\Models\Professional;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\UserCredit;
use App\Policies\ChatMessagePolicy;
use App\Policies\CreditPolicy;
use App\Policies\ProfessionalPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ServiceRequest::class => ServiceRequestPolicy::class,
        Professional::class => ProfessionalPolicy::class,
        Review::class => ReviewPolicy::class,
        ChatMessage::class => ChatMessagePolicy::class,
        User::class => UserPolicy::class,
        UserCredit::class => CreditPolicy::class,
    ];

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
        // Register policies
        Gate::policy(ServiceRequest::class, ServiceRequestPolicy::class);
        Gate::policy(Professional::class, ProfessionalPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(ChatMessage::class, ChatMessagePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserCredit::class, CreditPolicy::class);
    }
}
