<?php

namespace Tests\Feature\Tenant;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use DB;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class CategoryActionsTest extends TenantTestCase
{
    /**
     */
    public function getCategory()
    {
        $category = Category::firstWhere('event_id', '1');
        $resultSet = DB::table('event_user')->where('event_id', '2')->where('ability', 'seller')->select('user_id')->get();
        $userId = $resultSet->first()->user_id;

        Sanctum::actingAs(
            User::findOrFail($userId),
            []
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/categories/{$category->id}");
    }

    /**
     */
    public function postCategory()
    {
        $event = Event::inRandomOrder()->first();

        Sanctum::actingAs(
            User::factory()->makeOne(),
            []
        );

        $response = $this->postJson("{$this->domainWithScheme}/api/events/{$event->id}/categories", [
            //
        ]);
    }
}
