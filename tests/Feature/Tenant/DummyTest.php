<?php

namespace Tests\Feature\Tenant;

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
    public function getEvents_WhenAdminOrCoordinator_Returns200()
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
    public function getEvent_WhenAdminOrCoordinator_Returns200()
    {
        $event = Event::inRandomOrder()->first();

        Sanctum::actingAs(
            User::firstWhere(['ability' => 'admin']),
            []
        );

        $response = $this->json('GET', "{$this->domainWithScheme}/api/events/{$event->id}");
    }
}
