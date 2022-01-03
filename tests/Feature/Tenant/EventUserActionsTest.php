<?php

namespace Tests\Feature\Tenant;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class EventUserActionsTest extends TenantTestCase
{
    public function getTopLevelAbilities()
    {
        return [
            'admin' => ['admin'],
            'manager' => ['manager'],
            'seller' => ['seller']
        ];
    }

    /**
     * Test with manager ability.
     * @test
     * @dataProvider getTopLevelAbilities
     */
    public function upsert_WithValidInput_Returns201($ability)
    {
        Sanctum::actingAs(
            User::factory()->makeOne(),
            ["{$ability}"]
        );

        $event = Event::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        DB::beginTransaction();
        $response = $this->putJson("{$this->domainWithScheme}/api/events/{$event->id}/users/{$user->id}", [
            'data' => [
                'ability' => 'manager'
            ]
        ]);

        if ($ability == 'admin' || $ability == 'manager') {
            $response->assertCreated();
            $this->assertDatabaseHas('event_user', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'ability' => 'manager'
            ]);
        } else if ($ability == 'seller') {
            $response->assertForbidden();
            $this->assertDatabaseMissing('event_user', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'ability' => 'manager'
            ]);
        }

        DB::rollBack();
    }
}
