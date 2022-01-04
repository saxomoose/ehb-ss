<?php

namespace Tests\Feature\Tenant;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class EventUserActionsTest extends TenantTestCase
{
    public function getRoles()
    {
        return [
            'admin' => ['admin', ''],
            'coordinator' => ['coordinator', ''],
            'manager' => ['', 'manager'],
            'seller' => ['', 'seller']
        ];
    }

    /**
     * @test
     * @dataProvider getRoles
     */
    public function upsert_WithValidInput_Returns201($ability, $role)
    {
        DB::beginTransaction();

        $user = User::factory()->createOne(['ability' => $ability]);
        Sanctum::actingAs(
            $user,
            []
        );

        $event = Event::inRandomOrder()->first();
        if ($role == 'manager' || $role == 'seller') {
            $event->users()->attach($user, ['ability' => "{$role}"]);
        }
        $userModel = User::factory()->createOne();

        $response = $this->putJson("{$this->domainWithScheme}/api/events/{$event->id}/users/{$userModel->id}", [
            'data' => [
                'ability' => 'seller'
            ]
        ]);

        if ($ability == 'admin' || $role == 'manager') {
            $response->assertCreated();
            $this->assertDatabaseHas('event_user', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'ability' => 'seller'
            ]);
        } else if ($ability == 'coordinator' || $role == 'seller') {
            $response->assertForbidden();
            $this->assertDatabaseMissing('event_user', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'ability' => 'seller'
            ]);
        }

        DB::rollBack();
    }
}
