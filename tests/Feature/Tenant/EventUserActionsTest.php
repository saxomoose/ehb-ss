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
     * @dataProvider getRoles
     */
    public function upsert_WithValidInput_Returns201($ability, $role)
    {
        DB::beginTransaction();

        $event = Event::inRandomOrder()->first();
        $manager = $event->getManager();
        $user = User::factory()->createOne(['ability' => $ability]);
        if ($role == 'manager') {
            Sanctum::actingAs(
                $manager,
                []
            );
        } else {
            Sanctum::actingAs(
                $user,
                []
            );
        }
        if ($role == 'seller') {
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
                'user_id' => $userModel->id,
                'ability' => 'seller'
            ]);
        } else if ($ability == 'coordinator' || $role == 'seller') {
            $response->assertForbidden();
            $this->assertDatabaseMissing('event_user', [
                'event_id' => $event->id,
                'user_id' => $userModel->id,
                'ability' => 'seller'
            ]);
        }

        DB::rollBack();
    }
}
