<?php

namespace App\Providers;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventUser;
use App\Models\Item;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\BankAccountPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\EventPolicy;
use App\Policies\EventUserPolicy;
use App\Policies\ItemPolicy;
use App\Policies\TenantPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // BankAccount::class => BankAccountPolicy::class,
        // Category::class => CategoryPolicy::class,
        // Event::class => EventPolicy::class,
        // EventUser::class => EventUserPolicy::class,
        // Item::class => ItemPolicy::class,
        // Tenant::class => TenantPolicy::class,
        // Transaction::class => TransactionPolicy::class,
        // User::class => UserPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
