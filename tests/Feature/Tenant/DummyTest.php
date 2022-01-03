<?php

namespace Tests\Feature\Tenant;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class DummyTest extends TenantTestCase
{
    /**
     * @test
     */
    public function getEvents()
    {
        Sanctum::actingAs(
            User::firstWhere(['ability' => 'admin']),
            []
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/events");
    }

    /**
     * @test
     */
    public function getEvent()
    {
        $event = Event::inRandomOrder()->first();

        Sanctum::actingAs(
            User::firstWhere(['ability' => 'admin']),
            []
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/events/{$event->id}");
    }

    /**
     * @test
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
     * @test
     */
    public function postCategory()
    {
        // Sanctum::actingAs(
        //     User::factory(),
        //     []
        // );

        // $response = $this->json('GET', "{$this->domainWithScheme}/api/categories/{$category->id}");
    }
}
