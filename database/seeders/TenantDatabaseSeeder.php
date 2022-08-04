<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Event;
use App\Models\Item;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds on a tenant.
     *
     * @return void
     */
    public function run()
    {
        $resultSet = Tenant::select('name', 'tenancy_admin_email')
            ->where('id', tenant('id'))
            ->get();
        $resultSetObject = $resultSet->first();
        $tenantName = $resultSetObject->name;
        $adminEmail = $resultSetObject->tenancy_admin_email;

        $centralAdmin = DB::connection(config('tenancy.database.central_connection'))
            ->table('users')
            ->first();
        User::factory()->createOne([
            'email' => $adminEmail,
            'ability' => 'admin',
        ]);

        // Everything hereunder to be commented out in production.
        $bankAccount = BankAccount::factory()->createOne();
        $manager = User::factory()->createOne(['email' => 'manager@demo.backend.test', 'ability' => 'manager']);

        // 1 manager and 1 seller per event
        $events = Event::factory(2)
            ->for($manager)    
            ->for($bankAccount)
            ->hasAttached(User::factory()->count(1))
            ->create();

        // 2 item categories per event (4)
        $categories = collect();
        foreach ($events as $event) {
            $categories->push(Category::factory(2)
                ->for($event)
                ->create());
        }
        $flattenedCategories = $categories->flatten();

        // 5 item per category (20)
        $items = collect();
        foreach ($flattenedCategories as $category) {
            $items->push(Item::factory(5)
                ->for($category)
                ->create());
        }

        $flattenedItems = $items->flatten();


        // 2 transactions per seller (4). 5 items per transaction.
        $resultSet = DB::table('users as u')
            ->join('event_user as eu', 'u.id', '=', 'eu.user_id')
            ->select('u.id')
            ->get();
        $users = collect();
        foreach ($resultSet as $item) {
            $users->push(User::findOrFail($item->id));
        }

        foreach ($users as $user) {
            $randomItems = $flattenedItems->random(5);

            Transaction::factory(2)
                ->for($user)
                ->for($user->events->random())
                ->hasAttached($randomItems, ['quantity' => rand(1, 10), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()])
                ->create();
        }
    }
}
