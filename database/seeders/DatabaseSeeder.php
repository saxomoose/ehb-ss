<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $dispatcher = User::getEventDispatcher();
        User::unsetEventDispatcher();
        $user = User::create([
            'email' => 'mathieu.tulpinck@student.ehb.be',
            'ability' => 'admin'
        ]);

        User::setEventDispatcher($dispatcher);
        // To be commented out in production.
        $tenant = Tenant::factory()->create();
        $domain = strtolower($tenant->name) . '.' . config('tenancy.central_domains.0');
        $tenant->domains()->create(['domain' => $domain]);
    }
}
