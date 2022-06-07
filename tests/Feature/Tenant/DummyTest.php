<?php

namespace Tests\Feature\Tenant;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class DummyTest extends TenantTestCase
{
    use WithFaker;

    /**
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
     */
    public function seed_()
    {
        Sanctum::actingAs(
            User::factory()->makeOne(['ability' => 'coordinator']),
            []
        );


        $response = $this->postJson("{$this->domainWithScheme}/api/users", [
            'data' => [
                'email' => $this->faker->email(),
                'ability' => 'coordinator'
            ]
        ]);
    }
}
