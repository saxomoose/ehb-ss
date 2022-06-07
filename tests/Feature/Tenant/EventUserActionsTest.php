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
            'manager' => ['manager', 'manager'],
            'seller' => ['', 'seller']
        ];
    }

    /**
     */
    public function seed_WhenUserIsManager_Returns201()
    {
        Sanctum::actingAs(
            User::factory()->makeOne(['ability' => 'manager']),
            []
        );

        DB::beginTransaction();
        $response = $this->postJson("{$this->domainWithScheme}/api/users", [
            'data' => [
                'email' => $this->faker->email(),
                'ability' => 'coordinator'
            ]
        ]);

        DB::rollBack();
    }

    /**
     * @test
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
                ['']
            );
        } else {
            Sanctum::actingAs(
                $user,
                ['']
            );
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
        } else if ($role == 'seller') {
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
